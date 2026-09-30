<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return;
        }

        $column = $connection->selectOne("SHOW COLUMNS FROM `orders` WHERE Field = 'status'");

        if ($column === null || ! str_contains((string) ($column->Type ?? ''), 'enum')) {
            return;
        }

        if (str_contains((string) $column->Type, "'proforma'")) {
            return;
        }

        $connection->statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM(
            'draft',
            'proforma',
            'pending',
            'processing',
            'shipped',
            'delivered',
            'canceled',
            'returned'
        ) NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        // Intentionally no down: removing enum values risks data loss.
    }
};
