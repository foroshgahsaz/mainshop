<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! $this->indexExists('orders', 'orders_status_created_at_index')) {
                    $table->index(['status', 'created_at'], 'orders_status_created_at_index');
                }
                if (Schema::hasColumn('orders', 'representative_id')
                    && Schema::hasColumn('orders', 'stock_reserved')
                    && ! $this->indexExists('orders', 'orders_rep_status_reserved_index')) {
                    $table->index(
                        ['representative_id', 'status', 'stock_reserved'],
                        'orders_rep_status_reserved_index'
                    );
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (! $this->indexExists('payments', 'payments_status_paid_at_index')) {
                    $table->index(['status', 'paid_at'], 'payments_status_paid_at_index');
                }
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'created_by_representative_id')) {
            Schema::table('users', function (Blueprint $table) {
                if (! $this->indexExists('users', 'users_created_by_representative_id_index')) {
                    $table->index('created_by_representative_id', 'users_created_by_representative_id_index');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'product_family_id')
                    && Schema::hasColumn('products', 'is_active')
                    && ! $this->indexExists('products', 'products_rep_catalog_index')) {
                    $table->index(
                        ['is_active', 'product_family_id', 'product_plant_id', 'brand_id', 'product_template_id'],
                        'products_rep_catalog_index'
                    );
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if ($this->indexExists('orders', 'orders_status_created_at_index')) {
                    $table->dropIndex('orders_status_created_at_index');
                }
                if ($this->indexExists('orders', 'orders_rep_status_reserved_index')) {
                    $table->dropIndex('orders_rep_status_reserved_index');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if ($this->indexExists('payments', 'payments_status_paid_at_index')) {
                    $table->dropIndex('payments_status_paid_at_index');
                }
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if ($this->indexExists('users', 'users_created_by_representative_id_index')) {
                    $table->dropIndex('users_created_by_representative_id_index');
                }
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if ($this->indexExists('products', 'products_rep_catalog_index')) {
                    $table->dropIndex('products_rep_catalog_index');
                }
            });
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        $database = $connection->getDatabaseName();

        $result = $connection->selectOne(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
