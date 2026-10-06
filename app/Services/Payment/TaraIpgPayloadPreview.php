<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Services\Settings\SettingsService;

class TaraIpgPayloadPreview
{
    public function __construct(
        protected SettingsService $settings,
        protected TaraInvoiceItemBuilder $invoiceItems,
    ) {}

    /**
     * Payload fields sent to Tara getToken (no secrets).
     *
     * @return array<string, mixed>
     */
    public function forPayment(Payment $payment, ?Order $order = null): array
    {
        $order = $order ?? $payment->order;

        if ($order === null) {
            return [];
        }

        $order->loadMissing(['user', 'items']);
        $config = $this->settings->tara();
        $gatewayAmount = AmountConverter::toGateway($payment->amount, $config['amount_unit']);
        $invoiceItem = $this->invoiceItems->build($order, $payment, $config, $gatewayAmount);

        return [
            'order_tracking_code' => $order->tracking_code,
            'payment_tracking_code' => $payment->tracking_code,
            'amount_toman' => (int) $payment->amount,
            'amount_gateway_unit' => $gatewayAmount,
            'amount_unit' => $config['amount_unit'],
            'mobile' => $this->normalizeMobile((string) ($order->user?->phone ?? '')),
            'taraInvoiceItemList' => [$invoiceItem],
            'group' => $invoiceItem['group'],
            'groupTitle' => $invoiceItem['groupTitle'],
            'service_id' => (string) $config['service_id'],
        ];
    }

    /**
     * Preview before a Payment row exists (e.g. proforma with درگاه تارا).
     *
     * @return array<string, mixed>
     */
    public function forOrder(Order $order): array
    {
        $order->loadMissing(['user', 'items']);

        $previewPayment = new Payment([
            'tracking_code' => 'PREVIEW-'.substr($order->tracking_code, -8),
            'amount' => (int) $order->final_amount,
            'gateway' => 'tara',
        ]);
        $previewPayment->setRelation('order', $order);

        $payload = $this->forPayment($previewPayment, $order);
        $payload['is_preview'] = true;

        return $payload;
    }

    protected function normalizeMobile(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            $digits = '0'.$digits;
        }

        return $digits;
    }
}
