<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Auth\AdminImpersonationService;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function flushAccessMemo(): void
    {
        AdminAccess::flushMemo();
    }

    public function test_only_full_admin_can_start_impersonation_for_bankdar_and_rep(): void
    {
        $admin = User::query()->forceCreate([
            'name' => 'ادمین',
            'phone' => '09120000001',
            'password' => bcrypt('x'),
            'status' => true,
            'is_admin' => true,
        ]);

        $sm = User::query()->forceCreate([
            'name' => 'مدیر فروش',
            'phone' => '09120000002',
            'password' => bcrypt('x'),
            'status' => true,
            'is_sales_manager' => true,
        ]);

        $bankdar = User::query()->forceCreate([
            'name' => 'بنکدار',
            'phone' => '09120000003',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
        ]);

        $rep = User::query()->forceCreate([
            'name' => 'نماینده',
            'phone' => '09120000004',
            'password' => bcrypt('x'),
            'status' => true,
            'is_representative' => true,
        ]);

        $svc = app(AdminImpersonationService::class);

        $this->assertTrue($svc->canStart($admin, $bankdar));
        $this->assertTrue($svc->canStart($admin, $rep));
        $this->assertFalse($svc->canStart($sm, $bankdar));
        $this->assertFalse($svc->canStart($admin, $sm));
        $this->assertFalse($svc->canStart($admin, $admin));
    }

    public function test_admin_can_impersonate_bankdar_and_stop(): void
    {
        $admin = User::query()->forceCreate([
            'name' => 'ادمین',
            'phone' => '09120000011',
            'password' => bcrypt('x'),
            'status' => true,
            'is_admin' => true,
        ]);

        $bankdar = User::query()->forceCreate([
            'name' => 'بنکدار تست',
            'phone' => '09120000012',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
        ]);

        $this->actingAs($admin);
        $this->flushAccessMemo();

        $svc = app(AdminImpersonationService::class);
        $url = $svc->start($admin, $bankdar);

        $this->assertStringContainsString('/admin', $url);
        $this->assertTrue($svc->isActive());
        $this->assertTrue(auth()->user()->is($bankdar));
        $this->assertFalse(AdminAccess::isFullAdmin());
        $this->assertTrue(AdminAccess::isBankdarOnly());

        $stopUrl = $svc->stop();
        $this->assertStringContainsString('/admin/users', $stopUrl);
        $this->assertFalse($svc->isActive());
        $this->assertTrue(auth()->user()->is($admin));
        $this->assertTrue(AdminAccess::isFullAdmin());
    }

    public function test_impersonate_representative_lands_on_representative_panel(): void
    {
        $admin = User::query()->forceCreate([
            'name' => 'ادمین',
            'phone' => '09120000021',
            'password' => bcrypt('x'),
            'status' => true,
            'is_admin' => true,
        ]);

        $rep = User::query()->forceCreate([
            'name' => 'نماینده تست',
            'phone' => '09120000022',
            'password' => bcrypt('x'),
            'status' => true,
            'is_representative' => true,
        ]);

        $this->actingAs($admin);
        $svc = app(AdminImpersonationService::class);
        $url = $svc->start($admin, $rep);

        $this->assertStringContainsString('/representative', $url);
        $this->assertTrue(auth()->user()->is($rep));
    }

    public function test_stop_route_requires_active_impersonation_and_restores_admin(): void
    {
        $admin = User::query()->forceCreate([
            'name' => 'ادمین',
            'phone' => '09120000031',
            'password' => bcrypt('x'),
            'status' => true,
            'is_admin' => true,
        ]);

        $bankdar = User::query()->forceCreate([
            'name' => 'بنکدار',
            'phone' => '09120000032',
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
        ]);

        $this->actingAs($admin);
        app(AdminImpersonationService::class)->start($admin, $bankdar);

        $this->post(route('admin.impersonation.stop'))
            ->assertRedirect();

        $this->assertTrue(auth()->user()->is($admin));
        $this->assertFalse(app(AdminImpersonationService::class)->isActive());
    }

    public function test_sales_manager_cannot_use_stop_without_session(): void
    {
        $sm = User::query()->forceCreate([
            'name' => 'مدیر فروش',
            'phone' => '09120000041',
            'password' => bcrypt('x'),
            'status' => true,
            'is_sales_manager' => true,
        ]);

        $this->actingAs($sm)
            ->post(route('admin.impersonation.stop'))
            ->assertForbidden();
    }

    public function test_banner_context_has_no_db_dependency_when_inactive(): void
    {
        $svc = app(AdminImpersonationService::class);
        $this->assertNull($svc->bannerContext());
        $this->assertFalse($svc->isActive());
    }
}
