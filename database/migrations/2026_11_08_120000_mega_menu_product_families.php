<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_families', function (Blueprint $table) {
            $table->boolean('show_in_mega')->default(false)->after('is_active');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('product_family_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('product_families')
                ->nullOnDelete();
        });

        Schema::create('mega_menu_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_family_id')->constrained('product_families')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('mega_menu_links')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('label');
            $table->string('url', 2048);
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('admin_shop_preview')->default(false);
            $table->timestamps();

            $table->index(['product_family_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mega_menu_links');

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_family_id');
        });

        Schema::table('product_families', function (Blueprint $table) {
            $table->dropColumn('show_in_mega');
        });
    }
};
