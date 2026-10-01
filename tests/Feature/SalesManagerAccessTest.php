<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminAccess;
use Tests\TestCase;

class SalesManagerAccessTest extends TestCase
{
    public function test_sales_manager_can_access_admin_panel_but_not_full_resources(): void
    {
        $manager = new User([
            'is_sales_manager' => true,
            'is_admin' => false,
            'status' => true,
        ]);

        $this->assertTrue($manager->canAccessPanel(filament()->getPanel('admin')));
        $this->assertTrue(AdminAccess::canAccessAdminResource(\App\Filament\Resources\OrderResource::class));
        $this->assertFalse(AdminAccess::canManageShopInAdmin($manager));
    }

    public function test_customer_type_excludes_sales_manager(): void
    {
        $manager = User::factory()->create([
            'is_sales_manager' => true,
            'is_admin' => false,
            'status' => true,
        ]);

        $this->assertFalse($manager->isCustomer());
        $this->assertStringContainsString('مدیر فروش', $manager->staffRoleLabel());
    }
}
