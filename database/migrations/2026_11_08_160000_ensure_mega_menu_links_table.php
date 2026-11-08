<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** اگر migrate قبلی نیمه‌کاره مانده و جدول mega_menu_links ساخته نشده */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mega_menu_links')) {
            Schema::create('mega_menu_links', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('root_category_id')->nullable();
                $table->unsignedBigInteger('product_family_id')->nullable();
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->string('label');
                $table->string('url', 2048);
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->boolean('admin_shop_preview')->default(false);
                $table->timestamps();

                $table->index('root_category_id');
                $table->index(['product_family_id', 'position']);
                $table->index('parent_id');
                $table->index('category_id');
            });

            return;
        }

        if (! Schema::hasColumn('mega_menu_links', 'root_category_id')) {
            Schema::table('mega_menu_links', function (Blueprint $table) {
                $table->unsignedBigInteger('root_category_id')->nullable()->after('id');
                $table->index('root_category_id');
            });
        }
    }

    public function down(): void
    {
        // repair-only
    }
};
