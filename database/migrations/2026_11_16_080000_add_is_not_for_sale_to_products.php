<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'is_not_for_sale')) {
                $table->boolean('is_not_for_sale')->default(false)->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'is_not_for_sale')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('is_not_for_sale');
            });
        }
    }
};
