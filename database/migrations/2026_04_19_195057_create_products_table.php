<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            // دسته‌بندی محصول
            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            // نام محصول
            $table->string('name');

            // اسلاگ برای URL
            $table->string('slug')->unique();

            // توضیح کوتاه
            $table->string('short_description')->nullable();

            // توضیح کامل
            $table->text('description')->nullable();

            // قیمت
            $table->unsignedBigInteger('price');

            // قیمت با تخفیف
            $table->unsignedBigInteger('sale_price')->nullable();

            // موجودی
            $table->integer('stock')->default(0);

            // کد محصول
            $table->string('sku')->unique()->nullable();

            // وزن
            $table->integer('weight')->nullable();

            // وضعیت نمایش
            $table->boolean('is_active')->default(true);

            // محصول ویژه
            $table->boolean('is_featured')->default(false);

            // تعداد بازدید
            $table->unsignedInteger('views')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
