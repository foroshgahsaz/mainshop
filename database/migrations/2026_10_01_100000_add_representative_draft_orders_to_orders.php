<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (! Schema::hasColumn('orders', 'representative_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('representative_id')->nullable()->after('user_id');
            });
        }

        if (
            Schema::hasColumn('orders', 'representative_id')
            && Schema::hasTable('users')
            && ! $this->foreignKeyExists('orders', 'orders_representative_id_foreign')
        ) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('representative_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('orders', 'catalog_filters')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->json('catalog_filters')->nullable()->after('note');
            });
        }

        $this->ensureDraftOrderStatus();
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        if (Schema::hasColumn('orders', 'catalog_filters')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('catalog_filters');
            });
        }

        if ($this->foreignKeyExists('orders', 'orders_representative_id_foreign')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropForeign(['representative_id']);
            });
        }

        if (Schema::hasColumn('orders', 'representative_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('representative_id');
            });
        }
    }

    private function ensureDraftOrderStatus(): void
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $column = $connection->selectOne("SHOW COLUMNS FROM `orders` WHERE Field = 'status'");

        if ($column === null || ! str_contains((string) ($column->Type ?? ''), 'enum')) {
            return;
        }

        if (str_contains((string) $column->Type, "'draft'")) {
            return;
        }

        $connection->statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM(
            'draft',
            'pending',
            'processing',
            'shipped',
            'delivered',
            'canceled',
            'returned'
        ) NOT NULL DEFAULT 'pending'");
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
