<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasColumn('orders', 'stock_reserved_until')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_reserved_until')->nullable()->after('stock_reserved');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders') || ! Schema::hasColumn('orders', 'stock_reserved_until')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_reserved_until');
        });
    }
};
