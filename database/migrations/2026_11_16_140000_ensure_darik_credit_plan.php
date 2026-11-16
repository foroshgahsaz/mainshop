<?php

use App\Services\Shop\EnsureDarikCreditPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        app(EnsureDarikCreditPlan::class)->ensure();
    }

    public function down(): void
    {
        if (! Schema::hasTable('credit_plans')) {
            return;
        }

        DB::table('credit_plans')
            ->where('plan_url', EnsureDarikCreditPlan::PLAN_URL)
            ->where('name', EnsureDarikCreditPlan::PLAN_NAME)
            ->delete();
    }
};
