<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Cart\StockService;
use App\Services\Order\OrderActivityLogger;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ZarinpalGateway implements PaymentGatewayInterface
{
    public function __construct(
        protected PaymentActivityLogger $paymentLog,
        protected OrderActivityLogger $orderLog,
        protected StockService $stockService,
    ) {}

    public function initiate(Payment $payment, Order $order): string
    {
        $config = app(\App\Services\Settings\SettingsService::class)->zarinpal();
        $baseUrl = $config['sandbox']
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://payment.zarinpal.com/pg/v4/payment';

        $callbackUrl = url($config['callback_url']).'?payment='.$payment->tracking_code;

        $response = Http::post("{$baseUrl}/request.json", [
            'merchant_id' => $config['merchant_id'],
            'amount' => $payment->amount * 10,
            'callback_url' => $callbackUrl,
            'description' => "پرداخت سفارش {$order->tracking_code}",
            'metadata' => [
                'mobile' => $order->user->phone,
                'email' => $order->user->email,
            ],
        ]);

        $data = $response->json('data');

        if ($response->failed() || empty($data['authority'])) {
            $previous = $payment->status;
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'raw_response' => $response->json(),
            ]);

            $this->paymentLog->statusChanged($payment->fresh(), $previous, Payment::STATUS_FAILED, 'خطا در اتصال به درگاه');
            $this->orderLog->paymentLinked($order, $payment->tracking_code, Payment::STATUS_FAILED);

            throw new RuntimeException('خطا در اتصال به درگاه پرداخت.');
        }

        $payment->update([
            'transaction_id' => $data['authority'],
            'raw_response' => $response->json(),
        ]);

        $this->paymentLog->gatewayResponse(
            $payment->fresh(),
            'کاربر به درگاه زرین‌پال هدایت شد.',
            ['authority' => $data['authority']]
        );

        $startPayUrl = $config['sandbox']
            ? 'https://sandbox.zarinpal.com/pg/StartPay/'
            : 'https://www.zarinpal.com/pg/StartPay/';

        return $startPayUrl.$data['authority'];
    }

    public function verify(Payment $payment, string $authority, string $status): Payment
    {
        $previousStatus = $payment->status;

        if ($status !== 'OK' || $authority !== $payment->transaction_id) {
            $payment->update(['status' => Payment::STATUS_CANCELED]);

            $this->paymentLog->statusChanged($payment->fresh(), $previousStatus, Payment::STATUS_CANCELED, 'انصراف یا لغو توسط کاربر');
            $this->orderLog->paymentLinked($payment->order, $payment->tracking_code, Payment::STATUS_CANCELED);

            return $payment;
        }

        $config = app(\App\Services\Settings\SettingsService::class)->zarinpal();
        $baseUrl = $config['sandbox']
            ? 'https://sandbox.zarinpal.com/pg/v4/payment'
            : 'https://payment.zarinpal.com/pg/v4/payment';

        $response = Http::post("{$baseUrl}/verify.json", [
            'merchant_id' => $config['merchant_id'],
            'amount' => $payment->amount * 10,
            'authority' => $authority,
        ]);

        $code = $response->json('data.code');

        if ($response->successful() && in_array($code, [100, 101], true)) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'paid_at' => now(),
                'card_number' => $response->json('data.card_pan'),
                'raw_response' => $response->json(),
            ]);

            $payment = $payment->fresh();
            $order = $payment->order;
            $orderPrevious = $order->status;

            $this->stockService->decrementOrderItems($order);

            $order->update(['status' => Order::STATUS_PROCESSING]);

            $card = $payment->card_number ? ' کارت: '.$payment->card_number : '';
            $this->paymentLog->statusChanged($payment, $previousStatus, Payment::STATUS_SUCCESS, 'پرداخت تأیید شد.'.$card);
            $this->orderLog->paymentLinked($order->fresh(), $payment->tracking_code, Payment::STATUS_SUCCESS, 'پرداخت آنلاین موفق');

            if ($orderPrevious !== Order::STATUS_PROCESSING) {
                $this->orderLog->statusChanged($order->fresh(), $orderPrevious, Order::STATUS_PROCESSING);
            }

            return $payment;
        }

        $payment->update([
            'status' => Payment::STATUS_FAILED,
            'raw_response' => $response->json(),
        ]);

        $this->paymentLog->statusChanged($payment->fresh(), $previousStatus, Payment::STATUS_FAILED, 'تأیید درگاه ناموفق');
        $this->orderLog->paymentLinked($payment->order, $payment->tracking_code, Payment::STATUS_FAILED);

        return $payment;
    }
}
