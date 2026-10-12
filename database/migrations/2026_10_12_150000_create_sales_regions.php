<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_regions')) {
            Schema::create('sales_regions', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('name');
                $table->unsignedBigInteger('sales_manager_id')->nullable()->index();
                $table->timestamps();
            });
        }

        $this->ensureInnoDb('sales_regions');

        if (Schema::hasTable('provinces') && ! Schema::hasColumn('provinces', 'sales_region_id')) {
            Schema::table('provinces', function (Blueprint $table) {
                $table->unsignedBigInteger('sales_region_id')->nullable()->after('position')->index();
            });
        }

        $this->ensureForeignKey('sales_regions', 'sales_manager_id', 'users', 'sales_regions_sales_manager_id_foreign');
        $this->ensureForeignKey('provinces', 'sales_region_id', 'sales_regions', 'provinces_sales_region_id_foreign');
    }

    public function down(): void
    {
        if (Schema::hasTable('provinces') && Schema::hasColumn('provinces', 'sales_region_id')) {
            if ($this->foreignKeyExists('provinces', 'provinces_sales_region_id_foreign')) {
                Schema::table('provinces', fn (Blueprint $table) => $table->dropForeign(['sales_region_id']));
            }

            Schema::table('provinces', fn (Blueprint $table) => $table->dropColumn('sales_region_id'));
        }

        Schema::dropIfExists('sales_regions');
    }

    private function ensureInnoDb(string $table): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        $row = DB::selectOne(
            'SELECT engine FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [Schema::getConnection()->getDatabaseName(), $table]
        );

        if ($row && strcasecmp((string) ($row->engine ?? $row->ENGINE ?? ''), 'InnoDB') !== 0) {
            try {
                DB::statement("ALTER TABLE `{$table}` ENGINE=InnoDB");
            } catch (Throwable) {
            }
        }
    }

    private function ensureForeignKey(string $table, string $column, string $references, string $constraint): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasTable($table) || ! Schema::hasTable($references) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        if ($this->foreignKeyExists($table, $constraint)) {
            return;
        }

        try {
            Schema::table($table, function (Blueprint $blueprint) use ($column, $references) {
                $blueprint->foreign($column)->references('id')->on($references)->nullOnDelete();
            });
        } catch (Throwable) {
            // Column + index are enough; SalesRegion::deleting clears provinces when the FK is missing.
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $connection = Schema::getConnection();

        if ($connection->getDriverName() !== 'mysql') {
            return false;
        }

        return $connection->select(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE constraint_schema = ? AND table_name = ? AND constraint_name = ?
             AND constraint_type = ? LIMIT 1',
            [$connection->getDatabaseName(), $table, $constraint, 'FOREIGN KEY']
        ) !== [];
    }
};
