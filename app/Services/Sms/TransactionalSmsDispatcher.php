<?php

namespace App\Services\Sms;

use App\Jobs\SendTransactionalSmsJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Settings\TransactionalSmsSettingsService;
use App\Support\ShopLabels;
use Illuminate\Support\Facades\Bus;

class TransactionalSmsDispatcher
{
    public function __construct(
        protected TransactionalSmsSettingsService $settings,
    ) {}

    public function accountCreated(User $user): void
    {
        $this->queue('account_created', $user->phone, [
            'site_name' => site_name(),
            'name' => $user->name,
            'phone' => $user->phone,
        ]);
    }

    public function orderPlaced(Order $order): void
    {
        $order->loadMissing(['user', 'items']);
        $phone = $order->user?->phone;

        if (! $phone) {
            return;
        }

        $vars = $this->orderVariables($order);
        $this->queue('order_placed', $phone, $vars);
        $this->dispatchStaff('staff_new_order', $vars);
    }

    public function orderPaid(Order $order, Payment $payment): void
    {
        $order->loadMissing(['user', 'items']);
        $phone = $order->user?->phone;

        if (! $phone) {
            return;
        }

        $this->queue('order_paid', $phone, array_merge($this->orderVariables($order), [
            'paid_amount' => number_format((int) $payment->amount),
            'gateway' => ShopLabels::gateway($payment->gateway),
            'payment_tracking' => (string) ($payment->tracking_code ?? '—'),
        ]));
    }

    public function paymentFailed(Order $order, ?Payment $payment = null): void
    {
        $order->loadMissing('user');
        $phone = $order->user?->phone;

        if (! $phone) {
            return;
        }

        $this->queue('payment_failed', $phone, $this->orderVariables($order));
    }

    public function paymentPartialRemaining(Order $order, int $remainingAmount): void
    {
        $order->loadMissing('user');
        $phone = $order->user?->phone;

        if (! $phone) {
            return;
        }

        $this->queue('payment_partial_remaining', $phone, array_merge($this->orderVariables($order), [
            'remaining_amount' => number_format($remainingAmount),
        ]));
    }

    public function orderShipped(Order $order): void
    {
        $this->statusSms($order, 'order_shipped', [
            'tracking_suffix' => $order->shipping_tracking_code
                ? ' رهگیری: '.$order->shipping_tracking_code
                : '',
        ]);
    }

    public function orderDelivered(Order $order): void
    {
        $this->statusSms($order, 'order_delivered');
    }

    public function orderCanceled(Order $order): void
    {
        $this->statusSms($order, 'order_canceled');
    }

    public function orderExpiredUnpaid(Order $order): void
    {
        $this->statusSms($order, 'order_expired_unpaid');
    }

    public function proformaCreated(Order $order): void
    {
        $order->loadMissing(['user', 'items', 'representative']);
        $phone = $order->user?->phone;

        if ($phone) {
            $vars = array_merge($this->orderVariables($order), [
                'reserved_until' => $order->stock_reserved_until?->shopJalali() ?? '—',
            ]);
            $this->queue('proforma_created', $phone, $vars);
        }

        $repVars = array_merge($this->orderVariables($order), [
            'representative_name' => $order->representative?->name ?? '—',
            'reserved_until' => $order->stock_reserved_until?->shopJalali() ?? '—',
        ]);
        $this->dispatchStaff('staff_new_proforma', $repVars);
    }

    public function proformaReservationExpired(Order $order): void
    {
        $this->statusSms($order, 'proforma_reservation_expired');
    }

    public function proformaReservationExtended(Order $order): void
    {
        $order->loadMissing('user');
        $phone = $order->user?->phone;

        if (! $phone) {
            return;
        }

        $this->queue('proforma_reservation_extended', $phone, array_merge($this->orderVariables($order), [
            'reserved_until' => $order->stock_reserved_until?->shopJalali() ?? '—',
        ]));
    }

    /** @param  array<string, string>  $extra */
    protected function statusSms(Order $order, string $templateKey, array $extra = []): void
    {
        $order->loadMissing('user');
        $phone = $order->user?->phone;

        if (! $phone) {
            return;
        }

        $this->queue($templateKey, $phone, array_merge($this->orderVariables($order), $extra));
    }

    /** @return array<string, string> */
    protected function orderVariables(Order $order): array
    {
        $order->loadMissing(['user', 'items']);

        return [
            'site_name' => site_name(),
            'name' => $order->user?->name ?? 'مشتری',
            'phone' => $order->user?->phone ?? '—',
            'order_code' => (string) $order->tracking_code,
            'amount' => number_format((int) $order->final_amount),
            'payment_method' => ShopLabels::paymentMethod($order->payment_method),
            'items' => $this->formatOrderItems($order),
            'items_count' => (string) $order->items->sum('quantity'),
            'tracking_suffix' => '',
            'remaining_amount' => number_format((int) $order->remainingAmount()),
            'reserved_until' => $order->stock_reserved_until?->shopJalali() ?? '—',
            'representative_name' => $order->representative?->name ?? '—',
        ];
    }

    /** @param  array<string, string>  $variables */
    protected function dispatchStaff(string $templateKey, array $variables): void
    {
        foreach ($this->settings->staffPhoneList() as $phone) {
            $this->queue($templateKey, $phone, $variables);
        }
    }

    /** @param  array<string, string>  $variables */
    protected function queue(string $templateKey, string $phone, array $variables): void
    {
        if ($phone === '') {
            return;
        }

        Bus::dispatch((new SendTransactionalSmsJob($templateKey, $phone, $variables))->afterResponse());
    }

    protected function formatOrderItems(Order $order): string
    {
        $parts = $order->items
            ->map(fn ($item) => trim($item->product_name).' ×'.(int) $item->quantity)
            ->values()
            ->all();

        $text = implode('، ', $parts);

        if (mb_strlen($text) > 180) {
            return mb_substr($text, 0, 177).'...';
        }

        return $text !== '' ? $text : '—';
    }
}
