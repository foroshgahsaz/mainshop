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
    private static bool $ensuredThisProcess = false;

    /** @return list<string> */
    public function ensure(): array
    {
        $actions = [];

        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'deleted_at')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->softDeletes();
            });
            $actions[] = 'orders.deleted_at';
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'deleted_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->softDeletes();
            });
            $actions[] = 'users.deleted_at';
        }

        return $actions;
    }

    /**
     * یک‌بار در هر process (+ کش اپ) — برای جلوگیری از ۵۰۰ داشبورد وقتی ستون هنوز نیست.
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
            if (Cache::get('soft_deletes_schema_ok') === 1) {
                return [];
            }
        } catch (\Throwable) {
            // کش در دسترس نیست؛ ادامه با Schema::hasColumn
        }

        $actions = $this->ensure();

        try {
            Cache::forever('soft_deletes_schema_ok', 1);
        } catch (\Throwable) {
        }

        return $actions;
    }
}
