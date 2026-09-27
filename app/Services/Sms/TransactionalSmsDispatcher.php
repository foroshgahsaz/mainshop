<?php

namespace App\Services\Sms;

use App\Contracts\SmsSender;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Settings\TransactionalSmsSettingsService;
use App\Support\ShopLabels;
use Illuminate\Support\Facades\Log;

class TransactionalSmsDispatcher
{
    public function __construct(
        protected SmsSender $sms,
        protected TransactionalSmsSettingsService $settings,
        protected SmsTemplateRenderer $renderer,
    ) {}

    public function accountCreated(User $user): void
    {
        $this->dispatch('account_created', $user->phone, [
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

        $this->dispatch('order_placed', $phone, [
            'site_name' => site_name(),
            'name' => $order->user?->name ?? 'مشتری',
            'phone' => $phone,
            'order_code' => (string) $order->tracking_code,
            'amount' => number_format((int) $order->final_amount),
            'payment_method' => ShopLabels::paymentMethod($order->payment_method),
            'items' => $this->formatOrderItems($order),
            'items_count' => (string) $order->items->sum('quantity'),
        ]);
    }

    public function orderPaid(Order $order, Payment $payment): void
    {
        $order->loadMissing(['user', 'items']);
        $phone = $order->user?->phone;

        if (! $phone) {
            return;
        }

        $this->dispatch('order_paid', $phone, [
            'site_name' => site_name(),
            'name' => $order->user?->name ?? 'مشتری',
            'phone' => $phone,
            'order_code' => (string) $order->tracking_code,
            'amount' => number_format((int) $order->final_amount),
            'paid_amount' => number_format((int) $payment->amount),
            'gateway' => ShopLabels::gateway($payment->gateway),
            'payment_method' => ShopLabels::paymentMethod($order->payment_method),
            'payment_tracking' => (string) ($payment->tracking_code ?? '—'),
            'items' => $this->formatOrderItems($order),
            'items_count' => (string) $order->items->sum('quantity'),
        ]);
    }

    /** @param  array<string, string>  $variables */
    protected function dispatch(string $templateKey, string $phone, array $variables): void
    {
        $template = $this->settings->template($templateKey);

        if ($template === null || ! $template['enabled'] || $template['body'] === '') {
            return;
        }

        $message = $this->renderer->render($template['body'], $variables);

        if ($message === '') {
            return;
        }

        try {
            $this->sms->sendTransactional($phone, $message);
        } catch (\Throwable $e) {
            Log::warning('Transactional SMS failed', [
                'template' => $templateKey,
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);
        }
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
