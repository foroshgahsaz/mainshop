<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'proforma_planned_credit_amount')) {
                $table->unsignedBigInteger('proforma_planned_credit_amount')->nullable()->after('payment_method');
            }
            if (! Schema::hasColumn('orders', 'proforma_cash_gateway')) {
                $table->string('proforma_cash_gateway', 32)->nullable()->after('proforma_planned_credit_amount');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'proforma_cash_gateway')) {
                $table->dropColumn('proforma_cash_gateway');
            }
            if (Schema::hasColumn('orders', 'proforma_planned_credit_amount')) {
                $table->dropColumn('proforma_planned_credit_amount');
            }
        });
    }
};
