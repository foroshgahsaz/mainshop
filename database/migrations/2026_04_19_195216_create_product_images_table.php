<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {

            $table->id();

            // ارتباط با محصول
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            // مسیر تصویر
            $table->string('image');

            // تصویر اصلی محصول
            $table->boolean('is_primary')->default(false);

            // ترتیب نمایش
            $table->integer('position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
