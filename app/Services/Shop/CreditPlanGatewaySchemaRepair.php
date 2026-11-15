<?php

namespace App\Services\Shop;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * وقتی کد CreditPlan با درگاه merge شده ولی migrate ستون‌ها را نساخته.
 */
final class CreditPlanGatewaySchemaRepair
{
    private const CACHE_OK_KEY = 'credit_plan_gateway_schema_ok_v1';

    private static bool $ensuredThisProcess = false;

    public static function hasCheckoutColumns(): bool
    {
        try {
            if (! Schema::hasTable('credit_plans')) {
                return false;
            }

            return Schema::hasColumn('credit_plans', 'credit_gateway')
                && Schema::hasColumn('credit_plans', 'cash_gateway');
        } catch (\Throwable) {
            return false;
        }
    }

    /** @return list<string> */
    public function ensure(): array
    {
        $actions = [];

        if (Schema::hasTable('credit_plans')) {
            if (! Schema::hasColumn('credit_plans', 'credit_gateway')) {
                Schema::table('credit_plans', function (Blueprint $table): void {
                    $table->string('credit_gateway', 32)->nullable()->after('plan_url');
                });
                $actions[] = 'credit_plans.credit_gateway';
            }

            if (! Schema::hasColumn('credit_plans', 'cash_gateway')) {
                Schema::table('credit_plans', function (Blueprint $table): void {
                    $after = Schema::hasColumn('credit_plans', 'credit_gateway') ? 'credit_gateway' : 'plan_url';
                    $table->string('cash_gateway', 32)->nullable()->after($after);
                });
                $actions[] = 'credit_plans.cash_gateway';
            }
        }

        // بدون FK — خطای 1824 روی پروداکشن وقتی referenced table باز نمی‌شود
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'credit_plan_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->unsignedBigInteger('credit_plan_id')->nullable()->after('payment_method');
                $table->index('credit_plan_id');
            });
            $actions[] = 'orders.credit_plan_id';
        }

        return $actions;
    }

    /**
     * یک‌بار در هر process — برای صفحه ویرایش طرح قبل از ذخیره.
     *
     * @return list<string>
     */
    public function ensureOnce(): array
    {
        if (self::$ensuredThisProcess) {
            return [];
        }
        self::$ensuredThisProcess = true;

        try {
            if (Cache::get(self::CACHE_OK_KEY) === 1 && self::hasCheckoutColumns()) {
                return [];
            }
        } catch (\Throwable) {
            // ادامه
        }

        try {
            Cache::forget(self::CACHE_OK_KEY);
        } catch (\Throwable) {
        }

        $actions = $this->ensure();

        if (self::hasCheckoutColumns()) {
            try {
                Cache::forever(self::CACHE_OK_KEY, 1);
            } catch (\Throwable) {
            }
        }

        return $actions;
    }

    public static function forgetCache(): void
    {
        self::$ensuredThisProcess = false;
        try {
            Cache::forget(self::CACHE_OK_KEY);
        } catch (\Throwable) {
        }
    }
}
