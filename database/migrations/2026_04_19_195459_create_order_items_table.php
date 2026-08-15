<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {

            $table->id();

            // ارتباط با سفارش
            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // ارتباط با محصول
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // تعداد
            $table->unsignedInteger('quantity');

            // قیمت واحد محصول در لحظه خرید
            $table->unsignedBigInteger('price');

            // قیمت کل آیتم = قیمت × تعداد
            $table->unsignedBigInteger('total_price');

            // ذخیره نام محصول هنگام خرید
            $table->string('product_name');

            // ذخیره SKU محصول هنگام خرید (اختیاری)
            $table->string('sku')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
