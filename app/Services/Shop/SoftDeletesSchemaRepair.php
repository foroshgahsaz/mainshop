<?php

namespace App\Services\Shop;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * وقتی مدل SoftDeletes دارد ولی migrate ستون را نساخته (یا migrate وسط راه fail شده).
 */
final class SoftDeletesSchemaRepair
{
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
}
