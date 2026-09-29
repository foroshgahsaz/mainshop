<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_representative')) {
                $table->boolean('is_representative')->default(false)->after('is_author');
            }
            if (! Schema::hasColumn('users', 'created_by_representative_id')) {
                $table->foreignId('created_by_representative_id')
                    ->nullable()
                    ->after('is_representative')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });

        Schema::create('representative_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('max_active_reservations')->default(3);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('representative_profiles');

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'created_by_representative_id')) {
                $table->dropConstrainedForeignId('created_by_representative_id');
            }
            if (Schema::hasColumn('users', 'is_representative')) {
                $table->dropColumn('is_representative');
            }
        });
    }
};
