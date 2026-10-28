<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Safety net when 2026_10_27_140000 did not run on production (deploy without migrate).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('orders')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'proforma_planned_credit_amount')) {
                $table->unsignedBigInteger('proforma_planned_credit_amount')->nullable();
            }
            if (! Schema::hasColumn('orders', 'proforma_cash_gateway')) {
                $table->string('proforma_cash_gateway', 32)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty — do not drop columns that may have been added by the earlier migration.
    }
};
