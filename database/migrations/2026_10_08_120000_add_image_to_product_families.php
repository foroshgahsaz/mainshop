<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_families')) {
            return;
        }

        Schema::table('product_families', function (Blueprint $table) {
            if (! Schema::hasColumn('product_families', 'image')) {
                $table->string('image')->nullable()->after('slug');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_families')) {
            return;
        }

        Schema::table('product_families', function (Blueprint $table) {
            if (Schema::hasColumn('product_families', 'image')) {
                $table->dropColumn('image');
            }
        });
    }
};
