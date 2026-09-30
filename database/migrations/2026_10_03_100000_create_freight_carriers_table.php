<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freight_carriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('carrier_number', 64);
            $table->foreignId('province_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'province_id']);
        });

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'freight_carrier_id')) {
                    $table->foreignId('freight_carrier_id')
                        ->nullable()
                        ->after('shipping_method_id')
                        ->constrained('freight_carriers')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'freight_carrier_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropConstrainedForeignId('freight_carrier_id');
            });
        }

        Schema::dropIfExists('freight_carriers');
    }
};
