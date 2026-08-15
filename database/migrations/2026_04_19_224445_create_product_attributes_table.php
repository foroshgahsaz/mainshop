<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('attribute_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_required')->default(false);
            $table->boolean('is_variation')->default(true);
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();

            $table->unique(
                ['product_id', 'attribute_id'],
                'prod_attr_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
