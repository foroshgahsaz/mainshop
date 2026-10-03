<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Order\OrderActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected PaymentGatewayCatalog $catalog,
        protected PaymentActivityLogger $paymentLog,
        protected PaymentAuditLogger $audit,
        protected OrderActivityLogger $orderLog,
    ) {}

    public function gateway(?string $name = null): PaymentGatewayInterface
    {
        return $this->gateways->driver($name);
    }

    public function createForOrder(Order $order, ?string $gateway = null, ?int $paidByRepresentativeId = null): Payment
    {
        $remaining = $order->remainingAmount();

        if ($remaining <= 0) {
            throw new RuntimeException('این سفارش تسویه شده است.');
        }

        $gatewayName = $gateway ?: config('payment.default');
        $this->catalog->assertEnabled($gatewayName);

        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'paid_by_representative_id' => $paidByRepresentativeId,
            'amount' => $remaining,
            'gateway' => $gatewayName,
            'status' => Payment::STATUS_PENDING,
            'tracking_code' => strtoupper(Str::random(12)),
        ]);

        $this->audit->step(
            $payment,
            PaymentAuditStep::RECORD_CREATED,
            'payment_created',
            'رکورد پرداخت در سیستم ایجاد شد.',
            ['amount' => $remaining, 'gateway' => $gatewayName]
        );

        $this->paymentLog->created($payment);

        $note = $paidByRepresentativeId
            ? 'نماینده به درگاه پرداخت هدایت شد.'
            : 'در انتظار پرداخت در درگاه';

        $this->audit->step(
            $payment,
            PaymentAuditStep::ORDER_LINKED,
            'order_linked',
            'پرداخت به سفارش متصل شد.',
            ['order_id' => $order->id, 'order_tracking' => $order->tracking_code]
        );

        $this->orderLog->paymentLinked($payment->order, $payment->tracking_code, $payment->status, $note);

        return $payment;
    }

    public function initiate(Payment $payment, Order $order): string
    {
        $this->audit->step(
            $payment,
            PaymentAuditStep::INITIATE_START,
            'initiate_start',
            'شروع اتصال به درگاه پرداخت.',
            ['order_id' => $order->id]
        );

        if (! $order->stock_reserved) {
            $this->audit->failure(
                $payment,
                PaymentAuditStep::GATEWAY_CHECK,
                'stock_not_reserved',
                'موجودی سفارش رزرو نشده است.'
            );

            throw new RuntimeException('موجودی این سفارش رزرو نشده است.');
        }

        if ($order->remainingAmount() <= 0) {
            $this->audit->failure(
                $payment,
                PaymentAuditStep::GATEWAY_CHECK,
                'order_already_paid',
                'سفارش قبلاً تسویه شده است.'
            );

            throw new RuntimeException('این سفارش تسویه شده است.');
        }

        $this->catalog->assertEnabled($payment->gateway);

        $this->audit->step(
            $payment,
            PaymentAuditStep::GATEWAY_CHECK,
            'gateway_enabled',
            'درگاه پرداخت فعال است.',
            ['gateway' => $payment->gateway]
        );

        try {
            $redirectUrl = $this->gateway($payment->gateway)->initiate($payment, $order);
        } catch (\Throwable $e) {
            $this->audit->failure(
                $payment->fresh(),
                PaymentAuditStep::GATEWAY_RESPONSE,
                'initiate_failed',
                'خطا در شروع پرداخت در درگاه.',
                $e
            );

            throw $e;
        }

        $this->audit->step(
            $payment->fresh(),
            PaymentAuditStep::REDIRECT_USER,
            'redirect_user',
            'آدرس هدایت کاربر به درگاه آماده شد.',
            ['redirect_host' => parse_url($redirectUrl, PHP_URL_HOST)]
        );

        return $redirectUrl;
    }

    public function verify(Payment $payment, string $authority, string $status): Payment
    {
        $this->audit->step(
            $payment,
            PaymentAuditStep::VERIFY_START,
            'verify_start',
            'شروع تأیید پرداخت از درگاه.',
            ['authority' => $authority, 'callback_status' => $status]
        );

        $fresh = $payment->fresh();

        if ($fresh && $fresh->status === Payment::STATUS_SUCCESS) {
            $this->audit->step(
                $fresh,
                PaymentAuditStep::FINALIZED,
                'already_success',
                'پرداخت قبلاً موفق ثبت شده بود (بدون تغییر).'
            );

            return $fresh;
        }

        try {
            $result = $this->gateway($payment->gateway)->verify($payment, $authority, $status);
        } catch (\Throwable $e) {
            $this->audit->failure(
                $payment,
                PaymentAuditStep::VERIFY_RESULT,
                'verify_exception',
                'خطای غیرمنتظره هنگام تأیید درگاه.',
                $e
            );

            throw $e;
        }

        $this->audit->step(
            $payment,
            PaymentAuditStep::VERIFY_RESULT,
            'verify_gateway_result',
            'پاسخ تأیید درگاه دریافت شد.',
            [
                'successful' => $result->successful,
                'canceled' => $result->canceled,
                'message' => $result->message,
            ]
        );

        return DB::transaction(function () use ($payment, $result) {
            /** @var Payment $locked */
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === Payment::STATUS_SUCCESS) {
                return $locked;
            }

            $order = Order::query()->whereKey($locked->order_id)->lockForUpdate()->firstOrFail();
            $alreadyPaid = (int) $order->payments()
                ->where('status', Payment::STATUS_SUCCESS)
                ->where('id', '!=', $locked->id)
                ->sum('amount');
            $remaining = max(0, (int) $order->final_amount - $alreadyPaid);

            if ($remaining <= 0) {
                $previous = $locked->status;
                $locked->update([
                    'status' => Payment::STATUS_CANCELED,
                    'raw_response' => is_array($result->raw) ? $result->raw : $locked->raw_response,
                ]);

                $this->paymentLog->statusChanged(
                    $locked->fresh(),
                    $previous,
                    Payment::STATUS_CANCELED,
                    'پرداخت تکراری؛ سفارش قبلاً تسویه شده است'
                );

                $this->audit->step(
                    $locked->fresh(),
                    PaymentAuditStep::FINALIZED,
                    'duplicate_payment_canceled',
                    'پرداخت تکراری لغو شد؛ سفارش قبلاً تسویه شده بود.'
                );

                return $locked->fresh();
            }

            if ($result->canceled) {
                $previous = $locked->status;
                $locked->update([
                    'status' => Payment::STATUS_CANCELED,
                    'raw_response' => $result->raw,
                ]);

                $this->paymentLog->statusChanged($locked->fresh(), $previous, Payment::STATUS_CANCELED, $result->message);
                $this->orderLog->paymentLinked($order, $locked->tracking_code, Payment::STATUS_CANCELED);

                $this->audit->step(
                    $locked->fresh(),
                    PaymentAuditStep::FINALIZED,
                    'payment_canceled',
                    'پرداخت توسط کاربر لغو شد یا ناموفق بود.',
                    ['detail' => $result->message]
                );

                return $locked->fresh();
            }

            if (! $result->successful) {
                $previous = $locked->status;
                $locked->update([
                    'status' => Payment::STATUS_FAILED,
                    'raw_response' => $result->raw,
                ]);

                $this->paymentLog->statusChanged($locked->fresh(), $previous, Payment::STATUS_FAILED, $result->message);
                $this->orderLog->paymentLinked($order, $locked->tracking_code, Payment::STATUS_FAILED);

                $this->audit->failure(
                    $locked->fresh(),
                    PaymentAuditStep::FINALIZED,
                    'payment_failed',
                    'تأیید درگاه ناموفق بود.',
                    null,
                    ['detail' => $result->message]
                );

                return $locked->fresh();
            }

            $captured = min($result->paidAmount ?? $locked->amount, $remaining);

            if ($captured <= 0) {
                $previous = $locked->status;
                $locked->update([
                    'status' => Payment::STATUS_CANCELED,
                    'raw_response' => is_array($result->raw) ? $result->raw : $locked->raw_response,
                ]);

                $this->paymentLog->statusChanged(
                    $locked->fresh(),
                    $previous,
                    Payment::STATUS_CANCELED,
                    'مبلغ قابل اعمال روی سفارش صفر است'
                );

                return $locked->fresh();
            }

            $previous = $locked->status;
            $locked->update([
                'status' => Payment::STATUS_SUCCESS,
                'amount' => $captured,
                'paid_at' => now(),
                'card_number' => $result->cardPan,
                'raw_response' => $result->raw,
            ]);

            $locked = $locked->fresh();
            $orderPrevious = $order->status;
            $paidInFull = ($alreadyPaid + $captured) >= (int) $order->final_amount;
            $partialNote = $paidInFull
                ? 'پرداخت آنلاین موفق؛ سفارش تسویه شد'
                : 'پرداخت جزئی ثبت شد. مانده: '.number_format((int) $order->final_amount - $alreadyPaid - $captured).' تومان';

            if ($paidInFull && in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PROFORMA], true)) {
                $order->update([
                    'status' => Order::STATUS_PROCESSING,
                    'stock_reserved_until' => null,
                ]);
            }

            $card = $locked->card_number ? ' مرجع: '.$locked->card_number : '';
            $this->paymentLog->statusChanged($locked, $previous, Payment::STATUS_SUCCESS, 'پرداخت تأیید شد.'.$card);
            $this->orderLog->paymentLinked($order->fresh(), $locked->tracking_code, Payment::STATUS_SUCCESS, $partialNote);

            if ($paidInFull && $orderPrevious !== Order::STATUS_PROCESSING) {
                $this->orderLog->statusChanged($order->fresh(), $orderPrevious, Order::STATUS_PROCESSING);
            }

            if ($locked->wasPaidByRepresentative()) {
                $locked->loadMissing('paidByRepresentative');
                $order->loadMissing('user');
                $repName = $locked->paidByRepresentative?->name ?? 'نماینده';
                $customerName = $order->user?->name ?? 'مشتری';
                $this->orderLog->system(
                    $order->fresh(),
                    "پرداخت توسط نماینده {$repName} برای مشتری {$customerName} تأیید شد.",
                    'rep_payment_success'
                );
            }

            $final = $locked->fresh();
            $this->audit->step(
                $final,
                PaymentAuditStep::FINALIZED,
                'payment_finalized',
                'فرایند پرداخت در سیستم نهایی شد.',
                ['status' => $final->status]
            );

            return $final;
        });
    }
}
