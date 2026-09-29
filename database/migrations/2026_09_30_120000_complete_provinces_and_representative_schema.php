<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safety net when earlier representative migrations stopped mid-way.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provinces')) {
            return;
        }

        DB::statement('ALTER TABLE `provinces` ENGINE=InnoDB');

        if (Schema::hasTable('cities')) {
            DB::statement('ALTER TABLE `cities` ENGINE=InnoDB');
        }

        if (Schema::hasTable('user_addresses')) {
            DB::statement('ALTER TABLE `user_addresses` ENGINE=InnoDB');

            Schema::table('user_addresses', function (Blueprint $table) {
                if (! Schema::hasColumn('user_addresses', 'province_id')) {
                    $table->unsignedBigInteger('province_id')->nullable()->after('user_id');
                }
                if (! Schema::hasColumn('user_addresses', 'city_id')) {
                    $table->unsignedBigInteger('city_id')->nullable()->after('province_id');
                }
            });

            $this->addFkIfMissing('user_addresses', 'user_addresses_province_id_foreign', function (Blueprint $table) {
                $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();
            });

            if (Schema::hasTable('cities')) {
                $this->addFkIfMissing('user_addresses', 'user_addresses_city_id_foreign', function (Blueprint $table) {
                    $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
                });
            }
        }

        if (! Schema::hasTable('representative_profiles')) {
            return;
        }

        DB::statement('ALTER TABLE `representative_profiles` ENGINE=InnoDB');
    }

    public function down(): void
    {
        // Forward-only repair.
    }

    private function addFkIfMissing(string $table, string $constraint, callable $callback): void
    {
        if ($this->foreignKeyExists($table, $constraint)) {
            return;
        }

        Schema::table($table, $callback);
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
