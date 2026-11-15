<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'can_shop_gateway_test')) {
                $table->boolean('can_shop_gateway_test')->default(false)->after('status');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'can_shop_gateway_test')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('can_shop_gateway_test');
            });
        }
    }
};
