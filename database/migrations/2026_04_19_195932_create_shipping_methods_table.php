<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {

            $table->id();

            // نام روش ارسال
            $table->string('name');

            // توضیحات
            $table->text('description')->nullable();

            // هزینه ارسال
            $table->unsignedBigInteger('price')->default(0);

            // حداقل مبلغ سفارش برای ارسال رایگان
            $table->unsignedBigInteger('free_shipping_threshold')->nullable();

            // زمان تقریبی تحویل (روز)
            $table->unsignedInteger('estimated_days')->nullable();

            // فعال بودن روش
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
