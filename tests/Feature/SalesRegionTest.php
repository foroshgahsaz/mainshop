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
        foreach (['memoUser', 'memoIsFullAdmin', 'memoIsSalesManagerOnly'] as $p) {
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

    public function test_regions_scope_representative_management(): void
    {
        $p = [];
        foreach (['تهران', 'البرز', 'قم', 'گیلان'] as $i => $name) {
            $p[$i] = Province::query()->create(['name' => $name, 'slug' => 'p'.$i.uniqid(), 'position' => $i]);
        }

        $admin = $this->user('09130000001', ['is_admin' => 1]);
        $m1 = $this->user('09130000002', ['is_sales_manager' => 1]);
        $m2 = $this->user('09130000003', ['is_sales_manager' => 1]);

        $legacyRep = $this->user('09130000009', ['is_representative' => 1]);
        RepresentativeProfile::query()->create(['user_id' => $legacyRep->id, 'province_id' => $p[1]->id, 'max_active_reservations' => 3]);

        // Admin defines regions
        $this->actAs($admin);
        Livewire::test(CreateSalesRegion::class)
            ->fillForm(['name' => 'مرکز', 'sales_manager_id' => $m1->id, 'province_ids' => [$p[0]->id, $p[1]->id]])
            ->call('create')->assertHasNoFormErrors();
        $center = SalesRegion::query()->where('name', 'مرکز')->firstOrFail();
        $this->assertEqualsCanonicalizing([$p[0]->id, $p[1]->id], $center->provinces()->pluck('id')->all());
        $this->assertSame($m1->id, $legacyRep->representativeProfile()->first()->sales_manager_id, 'legacy rep reassigned to region manager');

        Livewire::test(CreateSalesRegion::class)
            ->fillForm(['name' => 'شمال', 'sales_manager_id' => $m2->id, 'province_ids' => [$p[3]->id]])
            ->call('create')->assertHasNoFormErrors();

        // province already in another region is rejected
        Livewire::test(CreateSalesRegion::class)
            ->fillForm(['name' => 'تکراری', 'sales_manager_id' => $m2->id, 'province_ids' => [$p[0]->id]])
            ->call('create')->assertHasFormErrors(['province_ids']);

        // one manager, several regions
        Livewire::test(CreateSalesRegion::class)
            ->fillForm(['name' => 'قم', 'sales_manager_id' => $m1->id, 'province_ids' => [$p[2]->id]])
            ->call('create')->assertHasNoFormErrors();
        $this->assertCount(3, app(SalesRegionService::class)->provinceIdsForManager($m1->id));

        // admin creates rep: manager required, province must be in manager's region
        $base = ['name' => 'نماینده جدید', 'phone' => '09130000010', 'status' => true, 'user_kind' => 'staff', 'is_representative' => true, 'password' => 'secret123'];
        Livewire::test(CreateUser::class)
            ->fillForm($base + ['rep_province_id' => $p[0]->id])
            ->call('create')->assertHasFormErrors(['rep_sales_manager_id']);
        Livewire::test(CreateUser::class)
            ->fillForm($base + ['rep_sales_manager_id' => $m2->id, 'rep_province_id' => $p[0]->id])
            ->call('create')->assertHasFormErrors(['rep_province_id']);
        Livewire::test(CreateUser::class)
            ->fillForm($base + ['rep_sales_manager_id' => $m2->id, 'rep_province_id' => $p[3]->id])
            ->call('create')->assertHasNoFormErrors();
        $northRep = User::query()->where('phone', '09130000010')->firstOrFail();
        $this->assertSame($m2->id, $northRep->representativeProfile->sales_manager_id);

        // sales manager creates rep: manager forced to self, province limited to own regions
        $this->actAs($m1);
        $smBase = ['name' => 'نماینده مدیر', 'phone' => '09130000011', 'status' => true, 'password' => 'secret123'];
        Livewire::test(CreateUser::class)
            ->fillForm($smBase + ['rep_province_id' => $p[3]->id])
            ->call('create')->assertHasFormErrors(['rep_province_id']);
        Livewire::test(CreateUser::class)
            ->fillForm($smBase + ['rep_province_id' => $p[2]->id, 'is_admin' => true])
            ->call('create')->assertHasNoFormErrors();
        $own = User::query()->where('phone', '09130000011')->firstOrFail();
        $this->assertTrue((bool) $own->is_representative);
        $this->assertFalse((bool) $own->is_admin);
        $this->assertSame($m1->id, $own->representativeProfile->sales_manager_id);

        // list only own reps
        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$own, $legacyRep])
            ->assertCanNotSeeTableRecords([$northRep]);

        // cannot edit other manager's rep
        $this->get('/admin/users/'.$northRep->id.'/edit')->assertForbidden();

        // can edit own rep but not escalate roles
        Livewire::test(EditUser::class, ['record' => $own->id])
            ->set('userEditTab', 'access')
            ->fillForm(['is_admin' => true])
            ->call('save');
        $this->assertFalse((bool) $own->fresh()->is_admin);

        // region manager change moves its reps
        $this->actAs($admin);
        Livewire::test(EditSalesRegion::class, ['record' => $center->id])
            ->fillForm(['sales_manager_id' => $m2->id])
            ->call('save')->assertHasNoFormErrors();
        $this->assertSame($m2->id, $legacyRep->representativeProfile()->first()->sales_manager_id);

    }

    public function test_regions_page_admin_only(): void
    {
        $admin = $this->user('09130000001', ['is_admin' => 1]);
        $m1 = $this->user('09130000002', ['is_sales_manager' => 1]);
        SalesRegion::query()->create(['name' => 'مرکز', 'sales_manager_id' => $m1->id]);
        $this->actAs($admin);
        $this->get('/admin/sales-regions')->assertOk()->assertSee('مرکز')->assertSee('افزودن منطقه');
        $this->get('/admin/sales-regions/create')->assertOk();
        $this->get('/admin')->assertOk()->assertSee('مناطق فروش');
    }

    public function test_regions_page_forbidden_for_sales_manager(): void
    {
        $m1 = $this->user('09130000002', ['is_sales_manager' => 1]);
        $this->actAs($m1);
        $this->get('/admin')->assertOk()->assertDontSee('افزودن منطقه')->assertSee('نمایندگان من');
        $this->get('/admin/sales-regions')->assertForbidden();
    }
}
