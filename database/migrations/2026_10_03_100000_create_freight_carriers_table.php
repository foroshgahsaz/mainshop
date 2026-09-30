<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('freight_carriers')) {
            Schema::create('freight_carriers', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('name');
                $table->string('carrier_number', 64);
                $table->foreignId('province_id')->constrained()->cascadeOnDelete();
                $table->foreignId('city_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['is_active', 'province_id']);
            });
        }

        if (! Schema::hasTable('orders')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'freight_carrier_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('freight_carrier_id')
                    ->nullable()
                    ->after('shipping_method_id');
                $table->index('freight_carrier_id');
            });
        }

        $this->ensureOrdersFreightCarrierForeignKey();
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'freight_carrier_id')) {
            if ($this->foreignKeyExists('orders', 'orders_freight_carrier_id_foreign')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropForeign(['freight_carrier_id']);
                });
            }

            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['freight_carrier_id']);
                $table->dropColumn('freight_carrier_id');
            });
        }

        Schema::dropIfExists('freight_carriers');
    }

    private function ensureOrdersFreightCarrierForeignKey(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable('freight_carriers') || ! Schema::hasColumn('orders', 'freight_carrier_id')) {
            return;
        }

        if ($this->foreignKeyExists('orders', 'orders_freight_carrier_id_foreign')) {
            return;
        }

        try {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('freight_carrier_id')
                    ->references('id')
                    ->on('freight_carriers')
                    ->nullOnDelete();
            });
        } catch (\Throwable) {
            // Column + index are enough for the app; FK can be added manually if needed.
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return false;
        }

        $database = $connection->getDatabaseName();

        $rows = $connection->select(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ?
             AND constraint_type = ? LIMIT 1',
            [$database, $table, $constraint, 'FOREIGN KEY']
        );

        return $rows !== [];
    }
};
