<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('credit_plans')) {
            Schema::table('credit_plans', function (Blueprint $table): void {
                if (! Schema::hasColumn('credit_plans', 'credit_gateway')) {
                    $table->string('credit_gateway', 32)->nullable()->after('plan_url');
                }
                if (! Schema::hasColumn('credit_plans', 'cash_gateway')) {
                    $table->string('cash_gateway', 32)->nullable()->after('credit_gateway');
                }
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table): void {
                if (! Schema::hasColumn('orders', 'credit_plan_id')) {
                    $table->foreignId('credit_plan_id')
                        ->nullable()
                        ->after('payment_method')
                        ->constrained('credit_plans')
                        ->nullOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'credit_plan_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('credit_plan_id');
            });
        }

        if (Schema::hasTable('credit_plans')) {
            Schema::table('credit_plans', function (Blueprint $table): void {
                if (Schema::hasColumn('credit_plans', 'cash_gateway')) {
                    $table->dropColumn('cash_gateway');
                }
                if (Schema::hasColumn('credit_plans', 'credit_gateway')) {
                    $table->dropColumn('credit_gateway');
                }
            });
        }
    }
};
