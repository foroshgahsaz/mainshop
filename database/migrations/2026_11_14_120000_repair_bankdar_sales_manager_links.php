<?php

use App\Services\Representative\BankdarHierarchyRepair;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(BankdarHierarchyRepair::class)->repairMissingSalesManagerLinks();
    }

    public function down(): void
    {
        //
    }
};
