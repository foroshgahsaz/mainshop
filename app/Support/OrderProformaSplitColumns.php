<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class OrderProformaSplitColumns
{
    public static function available(): bool
    {
        if (! Schema::hasTable('orders')) {
            return false;
        }

        return Schema::hasColumn('orders', 'proforma_planned_credit_amount')
            && Schema::hasColumn('orders', 'proforma_cash_gateway');
    }

    public static function migrationHint(): string
    {
        return 'ستون‌های طرح پرداخت پیش‌فاکتور در دیتابیس نیست. روی سرور اجرا کنید: php artisan migrate --force && php artisan shop:deploy-recover';
    }

    public static function ensureAvailable(): void
    {
        if (! static::available()) {
            throw new \RuntimeException(static::migrationHint());
        }
    }
}
