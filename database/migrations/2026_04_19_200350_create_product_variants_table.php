<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {

            $table->id();

            // محصول اصلی
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // عنوان اختیاری برای نمایش (مثلاً "آبی / 128GB")
            $table->string('name')->nullable();

            // قیمت عادی این واریانت
            $table->unsignedBigInteger('price');

            // قیمت حراج/تخفیفی (در صورت وجود)
            $table->unsignedBigInteger('sale_price')->nullable();

            // موجودی مخصوص این واریانت
            $table->unsignedInteger('stock')->default(0);

            // SKU اختصاصی
            $table->string('sku')->nullable()->unique();

            // وزن مخصوص این واریانت (مثلاً برای محاسبه هزینه ارسال)
            $table->unsignedInteger('weight')->nullable();

            // تصویر اختصاصی واریانت (در صورت نیاز)
            $table->string('image')->nullable();

            // فعال/غیرفعال
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
