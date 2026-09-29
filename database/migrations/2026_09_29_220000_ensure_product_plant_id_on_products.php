<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Completes product_plant_id when 210000 failed after creating product_plants.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_plants') || ! Schema::hasTable('products')) {
            return;
        }

        DB::statement('ALTER TABLE `product_plants` ENGINE=InnoDB');
        DB::statement('ALTER TABLE `products` ENGINE=InnoDB');

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'product_plant_id')) {
                $table->unsignedBigInteger('product_plant_id')
                    ->nullable()
                    ->after('product_family_id');
            }
        });

        if (! $this->foreignKeyExists('products', 'products_product_plant_id_foreign')) {
            Schema::table('products', function (Blueprint $table) {
                $table->foreign('product_plant_id')
                    ->references('id')
                    ->on('product_plants')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Forward-only repair.
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
};
