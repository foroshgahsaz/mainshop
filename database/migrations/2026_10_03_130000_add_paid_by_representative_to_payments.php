<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (Schema::hasColumn('payments', 'paid_by_representative_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('paid_by_representative_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasColumn('payments', 'paid_by_representative_id')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('paid_by_representative_id');
        });
    }
};
