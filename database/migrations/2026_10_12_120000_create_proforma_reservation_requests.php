<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('representative_profiles') && ! Schema::hasColumn('representative_profiles', 'sales_manager_id')) {
            Schema::table('representative_profiles', function (Blueprint $table) {
                $table->foreignId('sales_manager_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('proforma_reservation_requests')) {
            Schema::create('proforma_reservation_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('representative_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('sales_manager_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('status', 20)->default('pending')->index();
                $table->text('note')->nullable();
                $table->text('response_note')->nullable();
                $table->foreignId('handled_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('handled_at')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'status']);
                $table->index(['sales_manager_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('proforma_reservation_requests');

        if (Schema::hasColumn('representative_profiles', 'sales_manager_id')) {
            Schema::table('representative_profiles', function (Blueprint $table) {
                $table->dropConstrainedForeignId('sales_manager_id');
            });
        }
    }
};
