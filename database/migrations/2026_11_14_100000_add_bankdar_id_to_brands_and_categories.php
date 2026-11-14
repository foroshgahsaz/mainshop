<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('brands') && ! Schema::hasColumn('brands', 'bankdar_id')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->unsignedBigInteger('bankdar_id')->nullable()->after('position')->index();
            });

            Schema::table('brands', function (Blueprint $table): void {
                $table->foreign('bankdar_id')->references('id')->on('users')->nullOnDelete();
            });
        }

        if (Schema::hasTable('categories') && ! Schema::hasColumn('categories', 'bankdar_id')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->unsignedBigInteger('bankdar_id')->nullable()->after('position')->index();
            });

            Schema::table('categories', function (Blueprint $table): void {
                $table->foreign('bankdar_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('brands') && Schema::hasColumn('brands', 'bankdar_id')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->dropForeign(['bankdar_id']);
                $table->dropColumn('bankdar_id');
            });
        }

        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'bankdar_id')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->dropForeign(['bankdar_id']);
                $table->dropColumn('bankdar_id');
            });
        }
    }
};
