<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {

            $table->id();

            // کوپن استفاده شده
            $table->foreignId('coupon_id')
                ->constrained('coupons')
                ->cascadeOnDelete();

            // کاربری که استفاده کرده
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // سفارشی که کوپن روی آن اعمال شده
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // زمان استفاده
            $table->timestamp('used_at')->useCurrent();

            $table->timestamps();

            // جلوگیری از استفاده دوباره کاربر در یک سفارش
            $table->unique(['coupon_id', 'user_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
