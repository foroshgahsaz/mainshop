<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اگر migrate قبلی نیمه‌کاره مانده (ستون اضافه شده، جدول نه) — بدون خطای duplicate تکمیل می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_families') && ! Schema::hasColumn('product_families', 'show_in_mega')) {
            Schema::table('product_families', function (Blueprint $table) {
                $table->boolean('show_in_mega')->default(false)->after('is_active');
            });
        }

        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'product_family_id')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->unsignedBigInteger('product_family_id')->nullable()->after('parent_id');
                $table->index('product_family_id');
            });
        }

        if (! Schema::hasTable('mega_menu_links')) {
            Schema::create('mega_menu_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('product_family_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('label');
                $table->string('url', 2048);
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('admin_shop_preview')->default(false);
                $table->timestamps();

                $table->index(['product_family_id', 'position']);
                $table->index('parent_id');
                $table->index('category_id');
            });
        }
    }

    public function down(): void
    {
        // Repair migration — no rollback (use mega_menu down migration if needed).
    }
};
