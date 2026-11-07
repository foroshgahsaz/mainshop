<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Support\ShopStaffPreview;
use Tests\TestCase;

class ShopStaffPreviewTest extends TestCase
{
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
}
