<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('credit_plans')) {
            return;
        }

        $exists = DB::table('credit_plans')
            ->where(function ($q): void {
                $q->where('name', 'داریک')
                    ->orWhere('plan_url', '/plans/darik/')
                    ->orWhere('plan_url', 'like', '%/plans/darik%');
            })
            ->exists();

        if ($exists) {
            return;
        }

        $maxPosition = (int) DB::table('credit_plans')->max('position');

        $row = [
            'name' => 'داریک',
            'icon' => null,
            'short_description' => 'کیف پول اعتباری',
            'plan_url' => '/plans/darik/',
            'position' => $maxPosition + 1,
            'show_on_homepage_desktop' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('credit_plans', 'credit_gateway')) {
            $row['credit_gateway'] = 'darik';
        }

        if (Schema::hasColumn('credit_plans', 'cash_gateway')) {
            $row['cash_gateway'] = null;
        }

        DB::table('credit_plans')->insert($row);
    }

    public function down(): void
    {
        if (! Schema::hasTable('credit_plans')) {
            return;
        }

        DB::table('credit_plans')
            ->where('plan_url', '/plans/darik/')
            ->where('name', 'داریک')
            ->delete();
    }
};
