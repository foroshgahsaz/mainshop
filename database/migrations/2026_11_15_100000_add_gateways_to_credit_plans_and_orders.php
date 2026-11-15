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

        // بدون FK — روی پروداکشن MySQL/MariaDB خطای 1824 «Failed to open the referenced table»
        // برای credit_plans دیده شد (همان الگوی mega_menu / product_families).
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'credit_plan_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->unsignedBigInteger('credit_plan_id')->nullable()->after('payment_method');
                $table->index('credit_plan_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'credit_plan_id')) {
            Schema::table('orders', function (Blueprint $table): void {
                $table->dropIndex(['credit_plan_id']);
                $table->dropColumn('credit_plan_id');
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
