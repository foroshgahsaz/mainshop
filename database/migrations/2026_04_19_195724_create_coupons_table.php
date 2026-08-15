<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {

            $table->id();

            // کد تخفیف
            $table->string('code')->unique();

            // نوع تخفیف: درصدی یا مبلغ ثابت
            $table->enum('type', ['percent', 'fixed']);

            // مقدار تخفیف
            $table->unsignedInteger('value');

            // سقف تخفیف (فقط برای درصدی)
            $table->unsignedInteger('max_discount')->nullable();

            // تعداد کل مجاز استفاده
            $table->unsignedInteger('usage_limit')->nullable();

            // تعداد مجاز به ازای هر کاربر
            $table->unsignedInteger('usage_per_user')->default(1);

            // حداقل مبلغ سفارش برای استفاده از کد
            $table->unsignedBigInteger('minimum_order_amount')->nullable();

            // تاریخ شروع
            $table->timestamp('starts_at')->nullable();

            // تاریخ پایان
            $table->timestamp('expires_at')->nullable();

            // وضعیت کد تخفیف
            $table->boolean('is_active')->default(true);

            // توضیحات (اختیاری)
            $table->string('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
