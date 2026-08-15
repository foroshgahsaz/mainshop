<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('position');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_image')->nullable()->after('meta_keywords');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->string('robots', 50)->default('index,follow')->after('canonical_url');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('position');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_image')->nullable()->after('meta_keywords');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->string('robots', 50)->default('index,follow')->after('canonical_url');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('published_at');
            $table->string('meta_description', 500)->nullable()->after('meta_title');
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_image')->nullable()->after('meta_keywords');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->string('robots', 50)->default('index,follow')->after('canonical_url');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->string('meta_keywords')->nullable()->after('meta_description');
            $table->string('og_title')->nullable()->after('meta_keywords');
            $table->string('og_description', 500)->nullable()->after('og_title');
            $table->string('og_image')->nullable()->after('og_description');
            $table->string('canonical_url')->nullable()->after('og_image');
            $table->string('robots', 50)->default('index,follow')->after('canonical_url');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords', 'og_image', 'canonical_url', 'robots']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords', 'og_image', 'canonical_url', 'robots']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords', 'og_image', 'canonical_url', 'robots']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['meta_keywords', 'og_title', 'og_description', 'og_image', 'canonical_url', 'robots']);
        });
    }
};
