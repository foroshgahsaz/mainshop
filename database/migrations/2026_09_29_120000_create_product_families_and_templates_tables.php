<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('product_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_family_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'slug']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('product_family_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
            $table->foreignId('product_template_id')->nullable()->after('product_family_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_template_id');
            $table->dropConstrainedForeignId('product_family_id');
        });

        Schema::dropIfExists('product_templates');
        Schema::dropIfExists('product_families');
    }
};
