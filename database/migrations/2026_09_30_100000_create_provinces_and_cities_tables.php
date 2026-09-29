<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('provinces')) {
            Schema::create('provinces', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug', 191)->unique();
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
            });
        }

        DB::statement('ALTER TABLE `provinces` ENGINE=InnoDB');

        if (! Schema::hasTable('cities')) {
            Schema::create('cities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('province_id');
                $table->string('name');
                $table->string('slug', 191);
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();

                $table->unique(['province_id', 'slug']);
            });
        }

        DB::statement('ALTER TABLE `cities` ENGINE=InnoDB');

        if (! $this->foreignKeyExists('cities', 'cities_province_id_foreign')) {
            Schema::table('cities', function (Blueprint $table) {
                $table->foreign('province_id')
                    ->references('id')
                    ->on('provinces')
                    ->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('user_addresses')) {
            DB::statement('ALTER TABLE `user_addresses` ENGINE=InnoDB');
        }

        Schema::table('user_addresses', function (Blueprint $table) {
            if (! Schema::hasColumn('user_addresses', 'province_id')) {
                $table->unsignedBigInteger('province_id')->nullable()->after('user_id');
            }
            if (! Schema::hasColumn('user_addresses', 'city_id')) {
                $table->unsignedBigInteger('city_id')->nullable()->after('province_id');
            }
        });

        if (! $this->foreignKeyExists('user_addresses', 'user_addresses_province_id_foreign')) {
            Schema::table('user_addresses', function (Blueprint $table) {
                $table->foreign('province_id')
                    ->references('id')
                    ->on('provinces')
                    ->nullOnDelete();
            });
        }

        if (! $this->foreignKeyExists('user_addresses', 'user_addresses_city_id_foreign')) {
            Schema::table('user_addresses', function (Blueprint $table) {
                $table->foreign('city_id')
                    ->references('id')
                    ->on('cities')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('user_addresses', function (Blueprint $table) {
            if ($this->foreignKeyExists('user_addresses', 'user_addresses_city_id_foreign')) {
                $table->dropForeign('user_addresses_city_id_foreign');
            }
            if ($this->foreignKeyExists('user_addresses', 'user_addresses_province_id_foreign')) {
                $table->dropForeign('user_addresses_province_id_foreign');
            }
            if (Schema::hasColumn('user_addresses', 'city_id')) {
                $table->dropColumn('city_id');
            }
            if (Schema::hasColumn('user_addresses', 'province_id')) {
                $table->dropColumn('province_id');
            }
        });

        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
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
