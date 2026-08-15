<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_cart', function (Blueprint $table) {

            $table->id();

            // صاحب سبد خرید
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // محصول انتخاب شده
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // تعداد محصول در سبد
            $table->unsignedInteger('quantity')->default(1);

            // قیمت لحظه‌ای محصول هنگام اضافه شدن
            $table->unsignedBigInteger('price');

            // ذخیره نام محصول هنگام اضافه شدن
            $table->string('product_name');

            // کد SKU اگر وجود داشته باشد
            $table->string('sku')->nullable();

            // زمان اضافه شدن به سبد
            $table->timestamp('added_at')->useCurrent();

            $table->timestamps();

            // هر کاربر یک محصول را دوبار اضافه نکند
            $table->unique(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_cart');
    }
};
