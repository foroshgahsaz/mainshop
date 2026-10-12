<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ReservationRequestResource;
use App\Models\ProformaReservationRequest;
use App\Models\User;
use App\Services\Auth\AdminLoginGuard;
use App\Support\AdminAccess;
use Tests\TestCase;

class SalesManagerAccessTest extends TestCase
{
    public function test_sales_manager_can_access_orders_and_products_but_not_full_shop_admin(): void
    {
        $manager = User::make([
            'is_sales_manager' => true,
            'is_admin' => false,
            'status' => true,
        ]);
        $manager->id = 1;

        $this->actingAs($manager);

        $this->assertTrue($manager->canAccessPanel(filament()->getPanel('admin')));
        $this->assertTrue(AdminAccess::canAccessAdminResource(OrderResource::class));
        $this->assertTrue(AdminAccess::canAccessAdminResource(ProductResource::class));
        $this->assertTrue(AdminAccess::canManageProductsInAdmin($manager));
        $this->assertFalse(AdminAccess::canManageShopInAdmin($manager));
        $this->assertTrue(AdminAccess::canAccessAdminResource(ReservationRequestResource::class));
        $this->assertContains('filament.admin.resources.reservation-requests.*', AdminAccess::salesManagerAllowedRoutePatterns());
    }

    public function test_reservation_requests_are_scoped_to_own_or_unassigned_for_sales_manager(): void
    {
        $manager = User::make(['is_sales_manager' => true, 'is_admin' => false]);
        $manager->id = 7;

        $query = ProformaReservationRequest::query()->visibleTo($manager);
        $sql = str_replace(['"', '`'], '', $query->toSql());
        $this->assertStringContainsString('sales_manager_id = ?', $sql);
        $this->assertStringContainsString('sales_manager_id is null', $sql);
        $this->assertSame([7], $query->getBindings());

        $admin = User::make(['is_admin' => true]);
        $this->assertStringNotContainsString('sales_manager_id', ProformaReservationRequest::query()->visibleTo($admin)->toSql());

        $representative = User::make(['is_representative' => true]);
        $this->assertStringContainsString('1 = 0', ProformaReservationRequest::query()->visibleTo($representative)->toSql());
    }

    public function test_sales_manager_passes_admin_login_guard(): void
    {
        $manager = new User([
            'is_sales_manager' => true,
            'is_admin' => false,
            'status' => true,
        ]);

        $guard = app(AdminLoginGuard::class);

        $this->assertTrue($guard->canAccessAdminLogin($manager));
        $guard->assertAllowed($manager);
    }

    public function test_customer_type_excludes_sales_manager(): void
    {
        $manager = new User([
            'is_sales_manager' => true,
            'is_admin' => false,
            'status' => true,
        ]);

        $this->assertFalse($manager->isCustomer());
        $this->assertStringContainsString('مدیر فروش', $manager->staffRoleLabel());
    }
}
