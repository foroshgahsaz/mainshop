<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent safety net when 2026_10_08_120000 was not applied on a server.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_families') || Schema::hasColumn('product_families', 'image')) {
            return;
        }

        Schema::table('product_families', function (Blueprint $table) {
            $table->string('image')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_families') || ! Schema::hasColumn('product_families', 'image')) {
            return;
        }

        Schema::table('product_families', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
