<?php

namespace Tests\Feature;

use App\Filament\Resources\SalesRegionResource\Pages\CreateSalesRegion;
use App\Filament\Resources\SalesRegionResource\Pages\EditSalesRegion;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\Province;
use App\Models\RepresentativeProfile;
use App\Models\SalesRegion;
use App\Models\User;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesRegionTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $phone, array $flags): User
    {
        return User::query()->forceCreate(['name' => 'u'.$phone, 'phone' => $phone, 'password' => bcrypt('x'), 'status' => 1] + $flags);
    }

    private function resetAccessMemo(): void
    {
        foreach (['memoUser', 'memoIsFullAdmin', 'memoIsSalesManagerOnly', 'memoIsBankdarOnly'] as $p) {
            $r = new \ReflectionProperty(AdminAccess::class, $p);
            $r->setValue(null, null);
        }
        app()->forgetScopedInstances();
    }

    private function actAs(User $u): void
    {
        $this->resetAccessMemo();
        $this->actingAs($u);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_region_is_name_and_provinces_only_then_sales_manager_creates_bankdars(): void
    {
        $p = [];
        foreach (['تهران', 'البرز', 'قم', 'گیلان'] as $i => $name) {
            $p[$i] = Province::query()->create(['name' => $name, 'slug' => 'p'.$i.uniqid(), 'position' => $i]);
        }

        $admin = $this->user('09130000001', ['is_admin' => 1]);
        $sales = $this->user('09130000002', ['is_sales_manager' => 1]);

        $this->actAs($admin);
        Livewire::test(CreateSalesRegion::class)
            ->fillForm(['name' => 'مرکز', 'province_ids' => [$p[0]->id, $p[1]->id]])
            ->call('create')->assertHasNoFormErrors();
        $center = SalesRegion::query()->where('name', 'مرکز')->firstOrFail();
        $this->assertEqualsCanonicalizing([$p[0]->id, $p[1]->id], $center->provinces()->pluck('id')->all());

        Livewire::test(CreateSalesRegion::class)
            ->fillForm(['name' => 'شمال', 'province_ids' => [$p[3]->id]])
            ->call('create')->assertHasNoFormErrors();

        Livewire::test(CreateSalesRegion::class)
            ->fillForm(['name' => 'تکراری', 'province_ids' => [$p[0]->id]])
            ->call('create')->assertHasFormErrors(['province_ids']);

        // sales manager creates bankdar for Mazandaran-like province (گیلان here)
        $this->actAs($sales);
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'بنکدار گیلان',
                'phone' => '09130000020',
                'status' => true,
                'password' => 'secret123',
                'bankdar_province_id' => $p[3]->id,
            ])
            ->call('create')->assertHasNoFormErrors();

        $bankdar = User::query()->where('phone', '09130000020')->firstOrFail();
        $this->assertTrue((bool) $bankdar->is_bankdar);
        $this->assertFalse((bool) $bankdar->is_sales_manager);
        $this->assertSame($sales->id, (int) $bankdar->created_by_sales_manager_id);
        $this->assertSame($p[3]->id, (int) $bankdar->bankdar_province_id);

        // second bankdar same province allowed
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'بنکدار دوم',
                'phone' => '09130000021',
                'status' => true,
                'password' => 'secret123',
                'bankdar_province_id' => $p[3]->id,
            ])
            ->call('create')->assertHasNoFormErrors();

        // bankdar creates rep — province locked to own
        $this->actAs($bankdar);
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'نماینده گیلان',
                'phone' => '09130000030',
                'status' => true,
                'password' => 'secret123',
                'rep_city_id' => null,
            ])
            ->call('create')->assertHasNoFormErrors();

        $rep = User::query()->where('phone', '09130000030')->firstOrFail();
        $this->assertTrue((bool) $rep->is_representative);
        $this->assertSame($bankdar->id, (int) $rep->representativeProfile->sales_manager_id);
        $this->assertSame($p[3]->id, (int) $rep->representativeProfile->province_id);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$rep]);

        // other sales manager cannot see this bankdar/rep
        $otherSales = $this->user('09130000003', ['is_sales_manager' => 1]);
        $this->actAs($otherSales);
        Livewire::test(ListUsers::class)
            ->assertCanNotSeeTableRecords([$bankdar, $rep]);
    }

    public function test_representatives_for_region_match_provinces(): void
    {
        $pTehran = Province::query()->create(['name' => 'تهران', 'slug' => 'teh-'.uniqid(), 'position' => 1]);
        $pGilan = Province::query()->create(['name' => 'گیلان', 'slug' => 'gil-'.uniqid(), 'position' => 2]);

        $region = SalesRegion::query()->create(['name' => 'مرکز']);
        $pTehran->update(['sales_region_id' => $region->id]);

        $bankdar = $this->user('09130000040', ['is_bankdar' => 1, 'bankdar_province_id' => $pTehran->id]);
        $repIn = $this->user('09130000041', ['is_representative' => 1]);
        RepresentativeProfile::query()->create([
            'user_id' => $repIn->id,
            'sales_manager_id' => $bankdar->id,
            'province_id' => $pTehran->id,
            'max_active_reservations' => 3,
        ]);
        $repOut = $this->user('09130000042', ['is_representative' => 1]);
        RepresentativeProfile::query()->create([
            'user_id' => $repOut->id,
            'sales_manager_id' => $bankdar->id,
            'province_id' => $pGilan->id,
            'max_active_reservations' => 3,
        ]);

        $ids = app(SalesRegionService::class)->representativesForRegion($region)->pluck('id')->all();
        $this->assertContains($repIn->id, $ids);
        $this->assertNotContains($repOut->id, $ids);
    }

    public function test_edit_region_provinces_without_manager(): void
    {
        $admin = $this->user('09130000050', ['is_admin' => 1]);
        $p1 = Province::query()->create(['name' => 'آذربایجان', 'slug' => 'az-'.uniqid(), 'position' => 1]);
        $p2 = Province::query()->create(['name' => 'اردبیل', 'slug' => 'ar-'.uniqid(), 'position' => 2]);
        $region = SalesRegion::query()->create(['name' => 'شمال‌غرب']);
        $p1->update(['sales_region_id' => $region->id]);

        $this->actAs($admin);
        Livewire::test(EditSalesRegion::class, ['record' => $region->id])
            ->fillForm(['name' => 'شمال‌غرب', 'province_ids' => [$p1->id, $p2->id]])
            ->call('save')->assertHasNoFormErrors();

        $this->assertEqualsCanonicalizing(
            [$p1->id, $p2->id],
            $region->fresh()->provinces()->pluck('id')->all()
        );
    }
}
