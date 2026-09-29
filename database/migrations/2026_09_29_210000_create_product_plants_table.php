<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_plants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_family_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug', 191)->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'product_plant_id')) {
                $table->foreignId('product_plant_id')
                    ->nullable()
                    ->after('product_family_id')
                    ->constrained('product_plants')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_plant_id')) {
                $table->dropConstrainedForeignId('product_plant_id');
            }
        });

        Schema::dropIfExists('product_plants');
    }
};
