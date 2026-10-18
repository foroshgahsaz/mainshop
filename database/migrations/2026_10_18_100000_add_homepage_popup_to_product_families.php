<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_families', function (Blueprint $table) {
            if (! Schema::hasColumn('product_families', 'homepage_popup_enabled')) {
                $table->boolean('homepage_popup_enabled')->default(false)->after('position');
            }
        });

        if (! Schema::hasTable('product_family_homepage_popup_items')) {
            Schema::create('product_family_homepage_popup_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_family_id')->constrained()->cascadeOnDelete();
                $table->string('label');
                $table->string('url', 2048);
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['product_family_id', 'is_active', 'position']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('product_family_homepage_popup_items');

        Schema::table('product_families', function (Blueprint $table) {
            if (Schema::hasColumn('product_families', 'homepage_popup_enabled')) {
                $table->dropColumn('homepage_popup_enabled');
            }
        });
    }
};
