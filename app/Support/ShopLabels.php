<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Payment;

class ShopLabels
{
    public static function orderStatus(?string $status): string
    {
        return match ($status) {
            Order::STATUS_DRAFT => 'پیش‌نویس نماینده',
            Order::STATUS_PROFORMA => 'پیش‌فاکتور',
            Order::STATUS_PENDING => 'در انتظار',
            Order::STATUS_PROCESSING => 'در حال پردازش',
            Order::STATUS_SHIPPED => 'ارسال شده',
            Order::STATUS_DELIVERED => 'تحویل شده',
            Order::STATUS_CANCELED => 'لغو شده',
            Order::STATUS_RETURNED => 'مرجوعی',
            default => $status ?? '—',
        };
    }

    public static function paymentStatus(?string $status): string
    {
        return match ($status) {
            Payment::STATUS_PENDING => 'در انتظار',
            Payment::STATUS_SUCCESS => 'موفق',
            Payment::STATUS_FAILED => 'ناموفق',
            Payment::STATUS_CANCELED => 'لغو شده',
            Payment::STATUS_REFUNDED => 'مسترد شده',
            default => $status ?? '—',
        };
    }

    public static function paymentMethod(?string $method): string
    {
        if ($method === null || $method === '') {
            return '—';
        }

        $label = config("payment.gateways.{$method}.label");

        return match ($method) {
            'online' => 'پرداخت آنلاین',
            'cod' => 'پرداخت در محل',
            default => is_string($label) && $label !== '' ? $label : self::gateway($method),
        };
    }

    public static function gateway(?string $gateway): string
    {
        return match ($gateway) {
            'zarinpal' => 'زرین‌پال',
            'tara' => 'تارا',
            'bajet' => 'باجت‌پی',
            default => $gateway ?? '—',
        };
    }

    public static function orderNoteType(?string $type): string
    {
        return match ($type) {
            'system' => 'سیستم',
            'private' => 'یادداشت خصوصی',
            'customer' => 'یادداشت مشتری',
            default => $type ?? '—',
        };
    }

    public static function formatMoney(?int $amount): string
    {
        if ($amount === null) {
            return '—';
        }

        return number_format($amount).' تومان';
    }
}
