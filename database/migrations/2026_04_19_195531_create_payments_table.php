<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {

            $table->id();

            // سفارش مربوط به این پرداخت
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // کاربری که پرداخت را انجام داده
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // مبلغ پرداخت
            $table->unsignedBigInteger('amount');

            // درگاه پرداخت (مثلاً zarinpal, idpay, payir, ...)
            $table->string('gateway')->nullable();

            // وضعیت پرداخت
            $table->enum('status', [
                'pending',   // در انتظار پرداخت / ارسال به درگاه
                'success',   // پرداخت موفق
                'failed',    // پرداخت ناموفق
                'canceled',  // لغو شده توسط کاربر
                'refunded',  // برگشت داده شده
            ])->default('pending');

            // کد پیگیری داخلی که به کاربر نشان می‌دهیم
            $table->string('tracking_code')->nullable()->unique();

            // شناسه تراکنش درگاه
            $table->string('transaction_id')->nullable();

            // چهار رقم آخر کارت (اختیاری)
            $table->string('card_number')->nullable();

            // زمان پرداخت موفق
            $table->timestamp('paid_at')->nullable();

            // پاسخ خام درگاه (برای لاگ و دیباگ)
            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
