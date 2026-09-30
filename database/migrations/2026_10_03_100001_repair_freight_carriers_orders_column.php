<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recovery when 2026_10_03_100000 partially applied (freight_carriers exists, orders column/FK missing).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('freight_carriers')) {
            return;
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

        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if ($this->foreignKeyExists('orders', 'orders_freight_carrier_id_foreign')) {
            return;
        }

        if (! $this->tableIsInnoDb('freight_carriers') || ! $this->tableIsInnoDb('orders')) {
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
            // Index-only is acceptable for runtime; avoids MySQL 1824 on some hosts.
        }
    }

    public function down(): void
    {
        // No-op: handled by down() on create_freight_carriers migration.
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $rows = $connection->select(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ?
             AND constraint_type = ? LIMIT 1',
            [$database, $table, $constraint, 'FOREIGN KEY']
        );

        return $rows !== [];
    }

    private function tableIsInnoDb(string $table): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $row = $connection->selectOne(
            'SELECT ENGINE FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? LIMIT 1',
            [$database, $table]
        );

        return strtoupper((string) ($row->ENGINE ?? '')) === 'INNODB';
    }
};
