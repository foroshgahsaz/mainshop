<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variant_values', function (Blueprint $table) {

            $table->id();

            // واریانت محصول
            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            // مقدار ویژگی (مثلاً "قرمز" یا "128GB")
            $table->foreignId('attribute_value_id')
                ->constrained('attribute_values')
                ->cascadeOnDelete();

            $table->timestamps();

            // جلوگیری از تکرار یک مقدار ویژگی برای یک واریانت
            $table->unique(
                ['product_variant_id', 'attribute_value_id'],
                'pv_values_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variant_values');
    }
};
