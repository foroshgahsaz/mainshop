<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_families')) {
            Schema::create('product_families', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                // 191 chars: safe unique index length on MySQL utf8mb4 (max key ~1000 bytes).
                $table->string('slug', 191)->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });
        } else {
            $this->ensureProductFamiliesIndexes();
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

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'product_family_id')) {
                $table->foreignId('product_family_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('products', 'product_template_id')) {
                $table->foreignId('product_template_id')->nullable()->after('product_family_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'product_template_id')) {
                $table->dropConstrainedForeignId('product_template_id');
            }

            if (Schema::hasColumn('products', 'product_family_id')) {
                $table->dropConstrainedForeignId('product_family_id');
            }
        });

        Schema::dropIfExists('product_templates');
        Schema::dropIfExists('product_families');
    }

    private function ensureProductFamiliesIndexes(): void
    {
        if ($this->indexExists('product_families', 'product_families_slug_unique')) {
            return;
        }

        Schema::table('product_families', function (Blueprint $table) {
            $table->unique('slug');
        });
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
