<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {

            $table->id();

            // کاربر ثبت‌کننده سفارش
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // آدرس انتخاب شده برای این سفارش
            $table->foreignId('address_id')
                ->constrained('user_addresses')
                ->cascadeOnDelete();

            // مبلغ کل قبل از تخفیف
            $table->unsignedBigInteger('total_amount');

            // مبلغ نهایی بعد از تخفیف
            $table->unsignedBigInteger('final_amount');

            // هزینه ارسال
            $table->unsignedBigInteger('shipping_amount')->default(0);

            // تخفیف اعمال شده
            $table->unsignedBigInteger('discount_amount')->default(0);

            // روش پرداخت (مثلاً online, cod)
            $table->string('payment_method')->default('online');

            // وضعیت سفارش
            $table->enum('status', [
                'pending',      // ثبت شده
                'processing',   // در حال آماده‌سازی
                'shipped',      // ارسال شده
                'delivered',    // تحویل داده شده
                'canceled',     // لغو شده
                'returned',     // مرجوع شده
            ])->default('pending');

            // شماره پیگیری سفارش
            $table->string('tracking_code')->nullable()->unique();

            // توضیحات (اختیاری)
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
