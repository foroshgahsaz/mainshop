<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safety net when 2026_09_29_120000 stopped mid-way (e.g. unique index on slug failed).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_families') && ! $this->indexExists('product_families', 'product_families_slug_unique')) {
            DB::statement('ALTER TABLE `product_families` MODIFY `slug` VARCHAR(191) NOT NULL');

            Schema::table('product_families', function (Blueprint $table) {
                $table->unique('slug');
            });
        }

        if (! Schema::hasTable('product_templates')) {
            Schema::create('product_templates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
                $table->foreignId('product_family_id')->nullable()->constrained()->nullOnDelete();
                $table->string('name');
                $table->string('slug', 191);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['brand_id', 'slug']);
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'product_family_id')) {
                    $table->foreignId('product_family_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
                }

                if (! Schema::hasColumn('products', 'product_template_id')) {
                    $table->foreignId('product_template_id')->nullable()->after('product_family_id')->constrained()->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        // Forward-only repair migration.
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();

        $rows = $connection->select(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?
             LIMIT 1',
            [$database, $table, $index]
        );

        return $rows !== [];
    }
};
