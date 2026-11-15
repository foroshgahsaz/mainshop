<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\User;
use App\Support\ShopStaffPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopStaffPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_visibility_rules(): void
    {
        $activePublic = new Category(['is_active' => true, 'admin_shop_preview' => false]);
        $inactive = new Category(['is_active' => false, 'admin_shop_preview' => false]);
        $staffOnly = new Category(['is_active' => false, 'admin_shop_preview' => true]);
        $activeStaffOnly = new Category(['is_active' => true, 'admin_shop_preview' => true]);

        $this->assertTrue(ShopStaffPreview::modelVisibleOnShop($activePublic, false));
        $this->assertFalse(ShopStaffPreview::modelVisibleOnShop($inactive, false));
        $this->assertFalse(ShopStaffPreview::modelVisibleOnShop($staffOnly, false));
        $this->assertFalse(ShopStaffPreview::modelVisibleOnShop($activeStaffOnly, false));

        $this->assertTrue(ShopStaffPreview::modelVisibleOnShop($staffOnly, true));
        $this->assertTrue(ShopStaffPreview::modelVisibleOnShop($activeStaffOnly, true));
        $this->assertTrue(ShopStaffPreview::modelVisibleOnShop($activePublic, true));
        $this->assertFalse(ShopStaffPreview::modelVisibleOnShop($inactive, true));
    }

    public function test_gateway_test_user_can_see_preview_but_is_not_staff_admin_bar(): void
    {
        $tester = User::factory()->create([
            'status' => true,
            'is_admin' => false,
            'is_sales_manager' => false,
            'can_shop_gateway_test' => true,
        ]);

        $this->assertTrue($tester->canShopGatewayTest());
        $this->assertTrue(ShopStaffPreview::viewerCanSeePreview($tester));
        $this->assertFalse(ShopStaffPreview::viewerIsStaff($tester));

        $customer = User::factory()->create([
            'status' => true,
            'can_shop_gateway_test' => false,
        ]);
        $this->assertFalse(ShopStaffPreview::viewerCanSeePreview($customer));
    }
}
