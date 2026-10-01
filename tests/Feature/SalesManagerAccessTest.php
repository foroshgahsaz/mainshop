<?php

namespace Tests\Feature;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\ProductResource;
use App\Models\User;
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
