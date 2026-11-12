<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table): void {
                if (! Schema::hasColumn('users', 'is_bankdar')) {
                    $table->boolean('is_bankdar')->default(false)->after('is_sales_manager');
                }
                if (! Schema::hasColumn('users', 'bankdar_province_id')) {
                    $table->unsignedBigInteger('bankdar_province_id')->nullable()->after('is_bankdar')->index();
                }
                if (! Schema::hasColumn('users', 'created_by_sales_manager_id')) {
                    $table->unsignedBigInteger('created_by_sales_manager_id')->nullable()->after('bankdar_province_id')->index();
                }
            });

            $this->ensureForeignKey('users', 'bankdar_province_id', 'provinces', 'users_bankdar_province_id_foreign');
            $this->ensureForeignKey('users', 'created_by_sales_manager_id', 'users', 'users_created_by_sales_manager_id_foreign');
        }

        $this->migrateExistingRegionalManagersToBankdar();
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                if ($this->foreignKeyExists('users', 'users_bankdar_province_id_foreign')) {
                    $table->dropForeign(['bankdar_province_id']);
                }
                if ($this->foreignKeyExists('users', 'users_created_by_sales_manager_id_foreign')) {
                    $table->dropForeign(['created_by_sales_manager_id']);
                }
            }
            foreach (['created_by_sales_manager_id', 'bankdar_province_id', 'is_bankdar'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    protected function migrateExistingRegionalManagersToBankdar(): void
    {
        if (! Schema::hasColumn('users', 'is_bankdar')) {
            return;
        }

        $managerIds = collect();

        if (Schema::hasTable('sales_regions') && Schema::hasColumn('sales_regions', 'sales_manager_id')) {
            $managerIds = $managerIds->merge(
                DB::table('sales_regions')->whereNotNull('sales_manager_id')->pluck('sales_manager_id')
            );
        }

        if (Schema::hasTable('representative_profiles') && Schema::hasColumn('representative_profiles', 'sales_manager_id')) {
            $managerIds = $managerIds->merge(
                DB::table('representative_profiles')->whereNotNull('sales_manager_id')->pluck('sales_manager_id')
            );
        }

        $managerIds = $managerIds->map(fn ($id) => (int) $id)->unique()->filter()->values();

        foreach ($managerIds as $managerId) {
            $provinceId = null;

            if (Schema::hasTable('sales_regions') && Schema::hasTable('provinces')) {
                $regionId = DB::table('sales_regions')->where('sales_manager_id', $managerId)->value('id');
                if ($regionId) {
                    $provinceId = DB::table('provinces')->where('sales_region_id', $regionId)->orderBy('id')->value('id');
                }
            }

            DB::table('users')->where('id', $managerId)->update([
                'is_bankdar' => true,
                'is_sales_manager' => false,
                'bankdar_province_id' => $provinceId,
            ]);
        }
    }

    protected function ensureForeignKey(string $table, string $column, string $referencedTable, string $indexName): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || ! Schema::hasTable($referencedTable)) {
            return;
        }

        if ($this->foreignKeyExists($table, $indexName)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $referencedTable): void {
                $blueprint->foreign($column)->references('id')->on($referencedTable)->nullOnDelete();
            });
        } catch (\Throwable) {
            // Column + index are enough when FK cannot be added.
        }
    }

    protected function foreignKeyExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return false;
        }

        return $connection->select(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ?
             AND constraint_type = ? LIMIT 1',
            [$connection->getDatabaseName(), $table, $indexName, 'FOREIGN KEY']
        ) !== [];
    }
};
