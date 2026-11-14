<?php

namespace Tests\Feature;

use App\Models\Province;
use App\Models\RepresentativeProfile;
use App\Models\SalesRegion;
use App\Models\User;
use App\Services\Representative\BankdarHierarchyRepair;
use App\Services\Representative\SalesRegionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankdarHierarchyRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_repair_links_bankdar_to_sales_manager_from_legacy_profile(): void
    {
        $region = SalesRegion::query()->forceCreate(['name' => 'شمال', 'position' => 1]);
        $province = Province::query()->forceCreate([
            'name' => 'گیلان',
            'position' => 1,
            'sales_region_id' => $region->id,
        ]);

        $salesManager = User::query()->forceCreate([
            'name' => 'قدیمی',
            'phone' => '09120000001',
            'password' => bcrypt('x'),
            'status' => true,
            'is_sales_manager' => true,
        ]);

        $bankdar = User::query()->forceCreate([
            'name' => 'فریبرز',
            'phone' => '09120000002',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
            'created_by_sales_manager_id' => null,
            'bankdar_province_id' => null,
        ]);

        RepresentativeProfile::query()->forceCreate([
            'user_id' => $bankdar->id,
            'sales_manager_id' => $salesManager->id,
            'province_id' => $province->id,
            'max_active_reservations' => 3,
        ]);

        $result = app(BankdarHierarchyRepair::class)->repairMissingSalesManagerLinks();

        $this->assertSame(1, $result['linked']);
        $this->assertSame(1, $result['province_filled']);

        $bankdar->refresh();
        $this->assertSame((int) $salesManager->id, (int) $bankdar->created_by_sales_manager_id);
        $this->assertSame((int) $province->id, (int) $bankdar->bankdar_province_id);

        $this->assertTrue(
            app(SalesRegionService::class)->managesBankdar($salesManager, $bankdar)
        );
    }
}
