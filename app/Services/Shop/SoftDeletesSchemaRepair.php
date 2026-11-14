<?php

namespace App\Services\Shop;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * وقتی مدل SoftDeletes دارد ولی migrate ستون را نساخته (یا migrate وسط راه fail شده).
 */
final class SoftDeletesSchemaRepair
{
    private const CACHE_OK_KEY = 'soft_deletes_schema_ok_v2';

    private static bool $ensuredThisProcess = false;

    /** @var array<string, bool> */
    private static array $columnPresent = [];

    public static function hasDeletedAtColumn(string $table): bool
    {
        if (array_key_exists($table, self::$columnPresent)) {
            return self::$columnPresent[$table];
        }

        try {
            self::$columnPresent[$table] = Schema::hasTable($table)
                && Schema::hasColumn($table, 'deleted_at');
        } catch (\Throwable) {
            self::$columnPresent[$table] = false;
        }

        return self::$columnPresent[$table];
    }

    public static function forgetColumnCache(?string $table = null): void
    {
        if ($table === null) {
            self::$columnPresent = [];

            return;
        }

        unset(self::$columnPresent[$table]);
    }

    /** @return list<string> */
    public function ensure(): array
    {
        $actions = [];

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'deleted_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->softDeletes();
            });
            $actions[] = 'orders.deleted_at';
            self::forgetColumnCache('orders');
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->softDeletes();
            });
            $actions[] = 'users.deleted_at';
            self::forgetColumnCache('users');
        }

        return $actions;
    }

    /**
     * یک‌بار در هر process — فقط وقتی هر دو ستون واقعاً هستند کش «ok» می‌زند.
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
            if (Cache::get(self::CACHE_OK_KEY) === 1
                && self::hasDeletedAtColumn('orders')
                && self::hasDeletedAtColumn('users')
            ) {
                return [];
            }
        } catch (\Throwable) {
            // ادامه
        }

        // کش قدیمی/غلط را پاک کن تا دوباره تعمیر شود
        try {
            Cache::forget(self::CACHE_OK_KEY);
            Cache::forget('soft_deletes_schema_ok');
        } catch (\Throwable) {
        }

        $actions = $this->ensure();

        $ordersOk = self::hasDeletedAtColumn('orders');
        $usersOk = self::hasDeletedAtColumn('users');

        if ($ordersOk && $usersOk) {
            try {
                Cache::forever(self::CACHE_OK_KEY, 1);
            } catch (\Throwable) {
            }
        }

        return $actions;
    }
}
