<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Services\Checkout\CouponApplicabilityService;
use App\Services\Checkout\CouponService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_scoped_coupon_discounts_only_matching_lines(): void
    {
        $user = User::factory()->create();
        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat-'.uniqid(),
            'is_active' => true,
        ]);

        $included = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Included',
            'slug' => 'included-'.uniqid(),
            'price' => 1_000_000,
            'stock' => 5,
            'is_active' => true,
        ]);
        $other = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Other',
            'slug' => 'other-'.uniqid(),
            'price' => 2_000_000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $coupon = Coupon::query()->create([
            'code' => 'PROD10',
            'type' => Coupon::TYPE_PERCENT,
            'value' => 10,
            'scope_type' => Coupon::SCOPE_PRODUCTS,
            'is_active' => true,
        ]);
        $coupon->products()->sync([$included->id]);

        $lines = [
            ['product_id' => $included->id, 'quantity' => 1, 'price' => 1_000_000],
            ['product_id' => $other->id, 'quantity' => 1, 'price' => 2_000_000],
        ];

        $gross = 3_000_000;
        $service = app(CouponService::class);
        $result = $service->validate('PROD10', $user, $gross, null, $lines);

        $this->assertTrue($result['valid']);
        $this->assertSame(1_000_000, $result['eligible_subtotal']);
        $this->assertSame(100_000, $coupon->calculateDiscount($result['eligible_subtotal']));
    }

    public function test_category_scoped_coupon_rejects_cart_without_matching_category(): void
    {
        $user = User::factory()->create();
        $allowedCategory = Category::query()->create([
            'name' => 'Allowed',
            'slug' => 'allowed-'.uniqid(),
            'is_active' => true,
        ]);
        $otherCategory = Category::query()->create([
            'name' => 'Other cat',
            'slug' => 'other-cat-'.uniqid(),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $otherCategory->id,
            'name' => 'Out of scope',
            'slug' => 'out-'.uniqid(),
            'price' => 500_000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $coupon = Coupon::query()->create([
            'code' => 'CATONLY',
            'type' => Coupon::TYPE_FIXED,
            'value' => 50_000,
            'scope_type' => Coupon::SCOPE_CATEGORIES,
            'is_active' => true,
        ]);
        $coupon->categories()->sync([$allowedCategory->id]);

        $lines = [
            ['product_id' => $product->id, 'quantity' => 1, 'price' => 500_000],
        ];

        $result = app(CouponService::class)->validate('CATONLY', $user, 500_000, null, $lines);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('دسته‌بندی', $result['message'] ?? '');
    }

    public function test_category_scoped_coupon_uses_eligible_subtotal(): void
    {
        $user = User::factory()->create();
        $categoryA = Category::query()->create([
            'name' => 'A cat',
            'slug' => 'a-cat-'.uniqid(),
            'is_active' => true,
        ]);
        $categoryB = Category::query()->create([
            'name' => 'B cat',
            'slug' => 'b-cat-'.uniqid(),
            'is_active' => true,
        ]);

        $productA = Product::query()->create([
            'category_id' => $categoryA->id,
            'name' => 'A',
            'slug' => 'a-'.uniqid(),
            'price' => 800_000,
            'stock' => 1,
            'is_active' => true,
        ]);
        $productB = Product::query()->create([
            'category_id' => $categoryB->id,
            'name' => 'B',
            'slug' => 'b-'.uniqid(),
            'price' => 200_000,
            'stock' => 1,
            'is_active' => true,
        ]);

        $coupon = Coupon::query()->create([
            'code' => 'CATA',
            'type' => Coupon::TYPE_PERCENT,
            'value' => 25,
            'scope_type' => Coupon::SCOPE_CATEGORIES,
            'is_active' => true,
        ]);
        $coupon->categories()->sync([$categoryA->id]);

        $lines = [
            ['product_id' => $productA->id, 'quantity' => 1, 'price' => 800_000],
            ['product_id' => $productB->id, 'quantity' => 1, 'price' => 200_000],
        ];

        $eligible = app(CouponApplicabilityService::class)->eligibleSubtotal($coupon, $lines);

        $this->assertSame(800_000, $eligible);
        $this->assertTrue(
            app(CouponService::class)->validate('CATA', $user, 1_000_000, null, $lines)['valid']
        );
    }
}