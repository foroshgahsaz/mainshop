<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_families', function (Blueprint $table) {
            if (! Schema::hasColumn('product_families', 'show_on_homepage_sidebar')) {
                $table->boolean('show_on_homepage_sidebar')->default(false)->after('homepage_popup_enabled');
            }
        });

        Schema::table('product_family_homepage_popup_items', function (Blueprint $table) {
            if (! Schema::hasColumn('product_family_homepage_popup_items', 'image')) {
                $table->string('image', 2048)->nullable()->after('url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_family_homepage_popup_items', function (Blueprint $table) {
            if (Schema::hasColumn('product_family_homepage_popup_items', 'image')) {
                $table->dropColumn('image');
            }
        });

        Schema::table('product_families', function (Blueprint $table) {
            if (Schema::hasColumn('product_families', 'show_on_homepage_sidebar')) {
                $table->dropColumn('show_on_homepage_sidebar');
            }
        });
    }
};
