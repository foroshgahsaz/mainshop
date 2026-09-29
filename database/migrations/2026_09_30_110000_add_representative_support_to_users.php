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
            DB::statement('ALTER TABLE `users` ENGINE=InnoDB');
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_representative')) {
                $table->boolean('is_representative')->default(false)->after('is_author');
            }
            if (! Schema::hasColumn('users', 'created_by_representative_id')) {
                $table->unsignedBigInteger('created_by_representative_id')->nullable()->after('is_representative');
            }
        });

        if (! $this->foreignKeyExists('users', 'users_created_by_representative_id_foreign')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('created_by_representative_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('representative_profiles')) {
            Schema::create('representative_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->unsignedBigInteger('province_id')->nullable();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->unsignedSmallInteger('max_active_reservations')->default(3);
                $table->timestamps();
            });
        }

        DB::statement('ALTER TABLE `representative_profiles` ENGINE=InnoDB');

        if (! $this->foreignKeyExists('representative_profiles', 'representative_profiles_user_id_foreign')) {
            Schema::table('representative_profiles', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('provinces') && ! $this->foreignKeyExists('representative_profiles', 'representative_profiles_province_id_foreign')) {
            Schema::table('representative_profiles', function (Blueprint $table) {
                $table->foreign('province_id')->references('id')->on('provinces')->nullOnDelete();
            });
        }

        if (Schema::hasTable('cities') && ! $this->foreignKeyExists('representative_profiles', 'representative_profiles_city_id_foreign')) {
            Schema::table('representative_profiles', function (Blueprint $table) {
                $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('representative_profiles');

        Schema::table('users', function (Blueprint $table) {
            if ($this->foreignKeyExists('users', 'users_created_by_representative_id_foreign')) {
                $table->dropForeign('users_created_by_representative_id_foreign');
            }
            if (Schema::hasColumn('users', 'created_by_representative_id')) {
                $table->dropColumn('created_by_representative_id');
            }
            if (Schema::hasColumn('users', 'is_representative')) {
                $table->dropColumn('is_representative');
            }
        });
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
