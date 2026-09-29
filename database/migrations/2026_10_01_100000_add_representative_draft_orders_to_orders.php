<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            && ! $this->indexExists('orders', 'orders_representative_id_foreign')
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

        if ($this->indexExists('orders', 'orders_representative_id_foreign')) {
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
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $column = DB::selectOne("SHOW COLUMNS FROM `orders` WHERE Field = 'status'");

        if ($column === null || ! str_contains((string) ($column->Type ?? ''), 'enum')) {
            return;
        }

        if (str_contains((string) $column->Type, "'draft'")) {
            return;
        }

        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM(
            'draft',
            'pending',
            'processing',
            'shipped',
            'delivered',
            'canceled',
            'returned'
        ) NOT NULL DEFAULT 'pending'");
    }

    private function indexExists(string $table, string $indexName): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $result = DB::selectOne(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [DB::getConnection()->getDatabaseName(), $table, $indexName]
        );

        return $result !== null;
    }
};
