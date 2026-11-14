<?php

namespace Tests\Feature;

use App\Filament\Pages\BankdarSubtreePage;
use App\Models\Province;
use App\Models\RepresentativeProfile;
use App\Models\SalesRegion;
use App\Models\User;
use App\Services\Representative\BankdarSubtreeService;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BankdarSubtreePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtree_service_builds_sales_manager_to_reps_tree(): void
    {
        $region = SalesRegion::query()->forceCreate(['name' => 'شمال', 'position' => 1]);
        $province = Province::query()->forceCreate([
            'name' => 'گیلان',
            'position' => 1,
            'sales_region_id' => $region->id,
        ]);

        $sm = User::query()->forceCreate([
            'name' => 'قدیمی',
            'phone' => '09121110001',
            'password' => bcrypt('x'),
            'status' => true,
            'is_sales_manager' => true,
        ]);

        $bankdar = User::query()->forceCreate([
            'name' => 'فریبرز',
            'phone' => '09121110002',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
            'created_by_sales_manager_id' => $sm->id,
            'bankdar_province_id' => $province->id,
        ]);

        $rep = User::query()->forceCreate([
            'name' => 'نماینده رشت',
            'phone' => '09121110003',
            'password' => bcrypt('x'),
            'status' => true,
            'is_representative' => true,
        ]);

        RepresentativeProfile::query()->forceCreate([
            'user_id' => $rep->id,
            'sales_manager_id' => $bankdar->id,
            'province_id' => $province->id,
            'max_active_reservations' => 3,
        ]);

        $tree = app(BankdarSubtreeService::class)->treeForBankdar($bankdar);

        $this->assertSame('قدیمی', $tree['sales_manager']['name']);
        $this->assertSame('فریبرز', $tree['bankdar']['name']);
        $this->assertCount(1, $tree['representatives']);
        $this->assertSame('نماینده رشت', $tree['representatives'][0]['name']);
    }

    public function test_full_admin_can_open_subtree_page(): void
    {
        $admin = User::query()->forceCreate([
            'name' => 'ادمین',
            'phone' => '09121110009',
            'password' => bcrypt('x'),
            'status' => true,
            'is_admin' => true,
        ]);

        $bankdar = User::query()->forceCreate([
            'name' => 'بنکدار',
            'phone' => '09121110008',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
        ]);

        $this->actingAs($admin);
        foreach (['memoUser', 'memoIsFullAdmin', 'memoIsSalesManagerOnly', 'memoIsBankdarOnly'] as $p) {
            (new \ReflectionProperty(AdminAccess::class, $p))->setValue(null, null);
        }

        $this->assertTrue(BankdarSubtreePage::canAccess());

        Livewire::test(BankdarSubtreePage::class, ['record' => $bankdar->id])
            ->assertSuccessful()
            ->assertSee('زیرمجموعه')
            ->assertSee('بنکدار');
    }

    public function test_sales_manager_can_open_own_bankdar_subtree_only(): void
    {
        $manager = User::query()->forceCreate([
            'name' => 'مدیر فروش',
            'phone' => '09121110011',
            'password' => bcrypt('x'),
            'status' => true,
            'is_sales_manager' => true,
        ]);

        $own = User::query()->forceCreate([
            'name' => 'بنکدار من',
            'phone' => '09121110012',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
            'created_by_sales_manager_id' => $manager->id,
        ]);

        $other = User::query()->forceCreate([
            'name' => 'بنکدار دیگر',
            'phone' => '09121110013',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
            'created_by_sales_manager_id' => 999,
        ]);

        $this->actingAs($manager);
        foreach (['memoUser', 'memoIsFullAdmin', 'memoIsSalesManagerOnly', 'memoIsBankdarOnly'] as $p) {
            (new \ReflectionProperty(AdminAccess::class, $p))->setValue(null, null);
        }

        $this->assertTrue(AdminAccess::canViewBankdarSubtreeInAdmin($manager));
        $this->assertTrue(BankdarSubtreePage::canAccess());
        $this->assertContains('filament.admin.pages.bankdar-subtree', AdminAccess::limitedStaffAllowedRoutePatterns());

        Livewire::test(BankdarSubtreePage::class, ['record' => $own->id])
            ->assertSuccessful()
            ->assertSee('بنکدار من');

        Livewire::test(BankdarSubtreePage::class, ['record' => $other->id])
            ->assertForbidden();
    }
}
