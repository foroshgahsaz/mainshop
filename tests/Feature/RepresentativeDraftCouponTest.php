<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\FreightCarrier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Representative\RepresentativeDraftCouponService;
use App\Services\Representative\RepresentativeDraftOrderService;
use App\Services\Settings\SettingsService;
use Database\Seeders\IranLocationsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepresentativeDraftCouponTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(IranLocationsSeeder::class);
        app(SettingsService::class)->set('zarinpal', 'enabled', true);
    }

    public function test_representative_can_apply_coupon_to_draft_order(): void
    {
        [$rep, $order] = $this->draftOrderWithProduct(2_000_000);

        Coupon::query()->create([
            'code' => 'REP10',
            'type' => Coupon::TYPE_PERCENT,
            'value' => 10,
            'is_active' => true,
        ]);

        $this->actingAs($rep);

        $carrier = FreightCarrier::query()->create([
            'name' => 'Test Carrier',
            'phone' => '02100000000',
            'is_active' => true,
        ]);

        app(RepresentativeDraftOrderService::class)->syncFulfillment(
            $order,
            $carrier->id,
            'zarinpal',
        );

        $updated = app(RepresentativeDraftCouponService::class)->apply($order->fresh(), 'rep10', 'zarinpal');

        $this->assertNotNull($updated->coupon_id);
        $this->assertSame(200_000, (int) $updated->discount_amount);
        $this->assertSame(1_800_000, (int) $updated->final_amount);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function draftOrderWithProduct(int $price): array
    {
        $rep = User::factory()->create([
            'is_representative' => true,
            'status' => true,
        ]);

        $customer = User::factory()->create([
            'created_by_representative_id' => $rep->id,
        ]);

        $province = \App\Models\Province::query()->firstOrFail();
        $city = $province->cities()->firstOrFail();

        $customer->addresses()->create([
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'Test address',
            'is_default' => true,
        ]);

        $order = app(RepresentativeDraftOrderService::class)->createDraft($rep, $customer);

        $category = Category::query()->create([
            'name' => 'Cat',
            'slug' => 'cat-'.uniqid(),
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'Item',
            'slug' => 'item-'.uniqid(),
            'price' => $price,
            'stock' => 10,
            'is_active' => true,
        ]);

        app(RepresentativeDraftOrderService::class)->addProduct($order, $product, 1);

        return [$rep, $order->fresh()];
    }
}
