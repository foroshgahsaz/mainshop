<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_values', function (Blueprint $table) {

            $table->id();

            // ویژگی والد (مثل رنگ، سایز)
            $table->foreignId('attribute_id')
                ->constrained('attributes')
                ->cascadeOnDelete();

            // مقدار ویژگی (مثلاً قرمز، Large، 256GB)
            $table->string('value');

            // کد رنگ (در صورت نوع color)
            $table->string('color_code', 20)->nullable();

            // ترتیب نمایش در لیست
            $table->unsignedInteger('position')->default(0);

            // فعال بودن مقدار
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_values');
    }
};
