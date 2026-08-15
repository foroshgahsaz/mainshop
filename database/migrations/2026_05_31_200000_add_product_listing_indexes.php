<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['is_active', 'created_at'], 'products_active_created_idx');
            $table->index(['is_active', 'views'], 'products_active_views_idx');
            $table->index(['is_active', 'category_id'], 'products_active_category_idx');
            $table->index(['is_active', 'brand_id'], 'products_active_brand_idx');
            $table->index(['is_active', 'is_featured'], 'products_active_featured_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_created_idx');
            $table->dropIndex('products_active_views_idx');
            $table->dropIndex('products_active_category_idx');
            $table->dropIndex('products_active_brand_idx');
            $table->dropIndex('products_active_featured_idx');
        });
    }
};
