<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_regions')) {
            Schema::create('sales_regions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('sales_manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('provinces') && ! Schema::hasColumn('provinces', 'sales_region_id')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->foreignId('sales_region_id')
                    ->nullable()
                    ->after('position')
                    ->constrained('sales_regions')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('provinces', 'sales_region_id')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->dropConstrainedForeignId('sales_region_id');
            });
        }

        Schema::dropIfExists('sales_regions');
    }
};
