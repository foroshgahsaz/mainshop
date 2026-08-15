<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            // روش ارسال
            $table->foreignId('shipping_method_id')
                ->nullable()
                ->after('address_id')
                ->constrained('shipping_methods')
                ->nullOnDelete();

            // کد رهگیری ارسال
            $table->string('shipping_tracking_code')->nullable();

            // زمان ارسال
            $table->timestamp('shipped_at')->nullable();

            // زمان تحویل
            $table->timestamp('delivered_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            $table->dropForeign(['shipping_method_id']);

            $table->dropColumn([
                'shipping_method_id',
                'shipping_tracking_code',
                'shipped_at',
                'delivered_at'
            ]);
        });
    }
};
