<?php

namespace Tests\Feature;

use App\Filament\Pages\ProductListImport;
use App\Filament\Pages\ProductPriceStockUpdate;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OtpMessageResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ReservationRequestResource;
use App\Filament\Resources\UserResource;
use App\Models\ProformaReservationRequest;
use App\Models\RepresentativeProfile;
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
        $this->assertTrue(AdminAccess::canCreateBankdarsInAdmin($manager));
        $this->assertFalse(AdminAccess::canCreateRepresentativesInAdmin($manager));
        $this->assertTrue(UserResource::canCreate());
        $this->assertTrue(AdminAccess::canAccessAdminResource(ReservationRequestResource::class));
        $this->assertContains('filament.admin.resources.reservation-requests.*', AdminAccess::salesManagerAllowedRoutePatterns());
        $this->assertContains('filament.admin.resources.users.create', AdminAccess::salesManagerAllowedRoutePatterns());
        $this->assertFalse(OtpMessageResource::canAccess());
        $this->assertNotContains('filament.admin.resources.otp-messages.*', AdminAccess::salesManagerAllowedRoutePatterns());
    }

    public function test_bankdar_can_create_representatives_only(): void
    {
        $bankdar = User::make([
            'is_bankdar' => true,
            'is_sales_manager' => false,
            'is_admin' => false,
            'status' => true,
            'bankdar_province_id' => 5,
        ]);
        $bankdar->id = 9;

        $this->actingAs($bankdar);

        $this->assertTrue($bankdar->canAccessPanel(filament()->getPanel('admin')));
        $this->assertTrue(AdminAccess::isBankdarOnly($bankdar));
        $this->assertTrue(AdminAccess::canCreateRepresentativesInAdmin($bankdar));
        $this->assertFalse(AdminAccess::canCreateBankdarsInAdmin($bankdar));
        $this->assertTrue(AdminAccess::canAccessAdminResource(UserResource::class));
    }

    public function test_bankdar_can_manage_products_and_excel_import(): void
    {
        $bankdar = User::make([
            'is_bankdar' => true,
            'is_sales_manager' => false,
            'is_admin' => false,
            'status' => true,
            'bankdar_province_id' => 5,
        ]);
        $bankdar->id = 9;

        $this->actingAs($bankdar);

        $this->assertTrue(AdminAccess::isLimitedStaffOnly($bankdar));
        $this->assertTrue(AdminAccess::canManageProductsInAdmin($bankdar));
        $this->assertTrue(AdminAccess::canImportProductsInAdmin($bankdar));
        $this->assertTrue(AdminAccess::canAccessAdminResource(ProductResource::class));
        $this->assertTrue(ProductResource::canCreate());
        $this->assertTrue(ProductListImport::canAccess());
        $this->assertTrue(ProductPriceStockUpdate::canAccess());
        $this->assertContains('filament.admin.pages.product-list-import', AdminAccess::limitedStaffAllowedRoutePatterns());
        $this->assertContains('filament.admin.product-import.template', AdminAccess::limitedStaffAllowedRoutePatterns());
        $this->assertContains('filament.admin.resources.products.*', AdminAccess::limitedStaffAllowedRoutePatterns());
    }

    public function test_sales_manager_can_edit_assigned_bankdar_and_subtree_reps(): void
    {
        $manager = User::make(['is_sales_manager' => true, 'is_admin' => false]);
        $manager->id = 7;

        $ownBankdar = User::make(['is_bankdar' => true, 'created_by_sales_manager_id' => 7]);
        $ownBankdar->id = 8;

        $ownRep = User::make(['is_representative' => true]);
        $ownRep->id = 10;
        $ownRep->setRelation('representativeProfile', new RepresentativeProfile(['sales_manager_id' => 8]));

        $otherRep = User::make(['is_representative' => true]);
        $otherRep->id = 11;
        $otherRep->setRelation('representativeProfile', new RepresentativeProfile(['sales_manager_id' => 99]));

        $this->actingAs($manager);

        $service = app(\App\Services\Representative\SalesRegionService::class);
        $this->assertTrue($service->managesBankdar($manager, $ownBankdar));
        $this->assertTrue(UserResource::canEdit($ownBankdar));
        $this->assertSame(8, (int) $ownRep->representativeProfile->sales_manager_id);
        $this->assertSame(99, (int) $otherRep->representativeProfile->sales_manager_id);
        $this->assertFalse($service->managesBankdar($manager, User::make([
            'is_bankdar' => true,
            'created_by_sales_manager_id' => 55,
        ])));
    }

    public function test_reservation_requests_are_scoped_for_bankdar_and_sales_manager(): void
    {
        $bankdar = User::make(['is_bankdar' => true, 'is_admin' => false, 'is_sales_manager' => false]);
        $bankdar->id = 7;

        $query = ProformaReservationRequest::query()->visibleTo($bankdar);
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

    public function test_bankdar_passes_admin_login_guard(): void
    {
        $bankdar = new User([
            'is_bankdar' => true,
            'is_sales_manager' => false,
            'is_admin' => false,
            'status' => true,
        ]);

        $this->assertTrue(app(AdminLoginGuard::class)->canAccessAdminLogin($bankdar));
    }

    public function test_customer_type_excludes_sales_manager_and_bankdar(): void
    {
        $manager = new User([
            'is_sales_manager' => true,
            'is_admin' => false,
            'status' => true,
        ]);

        $this->assertFalse($manager->isCustomer());
        $this->assertStringContainsString('مدیر فروش', $manager->staffRoleLabel());

        $bankdar = new User([
            'is_bankdar' => true,
            'is_sales_manager' => false,
            'status' => true,
        ]);
        $this->assertFalse($bankdar->isCustomer());
        $this->assertStringContainsString('بنکدار', $bankdar->staffRoleLabel());
    }
}
