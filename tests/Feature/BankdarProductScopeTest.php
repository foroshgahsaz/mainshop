<?php

namespace Tests\Feature;

use App\Filament\Pages\ProductPriceStockUpdate;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\BankdarCatalogService;
use App\Support\AdminAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class BankdarProductScopeTest extends TestCase
{
    use RefreshDatabase;

    private function makeBankdar(string $phone = '09121110001'): User
    {
        return User::query()->forceCreate([
            'name' => 'بنکدار تست',
            'phone' => $phone,
            'password' => bcrypt('x'),
            'status' => true,
            'is_bankdar' => true,
        ]);
    }

    private function seedCatalogPair(User $owner, string $suffix): array
    {
        $brand = Brand::query()->forceCreate([
            'name' => 'برند '.$suffix,
            'slug' => 'brand-'.$suffix,
            'bankdar_id' => $owner->id,
            'is_active' => true,
        ]);
        $category = Category::query()->forceCreate([
            'name' => 'دسته '.$suffix,
            'slug' => 'cat-'.$suffix,
            'bankdar_id' => $owner->id,
            'is_active' => true,
        ]);
        $product = Product::query()->create([
            'name' => 'محصول '.$suffix,
            'slug' => 'p-'.$suffix,
            'sku' => 'SKU-'.$suffix,
            'price' => 1000,
            'stock' => 5,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        return [$brand, $category, $product];
    }

    public function test_bankdar_price_page_lists_only_own_catalog_products(): void
    {
        $bankdar = $this->makeBankdar();
        $other = $this->makeBankdar('09121110002');

        [, , $mine] = $this->seedCatalogPair($bankdar, 'mine');
        [, , $theirs] = $this->seedCatalogPair($other, 'other');

        $this->actingAs($bankdar);
        AdminAccess::flushMemo();

        $ids = app(BankdarCatalogService::class)
            ->scopeProductsForActor(Product::query())
            ->pluck('id')
            ->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);

        $page = Livewire::test(ProductPriceStockUpdate::class);
        $visibleIds = collect($page->instance()->getProductsProperty()->items())->pluck('id')->all();
        $this->assertSame([$mine->id], array_values($visibleIds));

        try {
            $page->call('toggleProductActive', $theirs->id);
            $this->fail('Expected 403 when toggling another bankdar product');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_bankdar_cannot_change_site_sales_toggles(): void
    {
        $bankdar = $this->makeBankdar('09121110003');
        $this->actingAs($bankdar);
        AdminAccess::flushMemo();

        $page = Livewire::test(ProductPriceStockUpdate::class);
        $this->assertFalse($page->instance()->canManageSiteSalesToggles());

        try {
            $page->set('hideShopPrices', true);
            $this->fail('Expected 403 when bankdar changes site sales toggle');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_bankdar_category_and_brand_options_are_scoped(): void
    {
        $bankdar = $this->makeBankdar('09121110004');
        $other = $this->makeBankdar('09121110005');

        [$ownBrand, $ownCategory] = $this->seedCatalogPair($bankdar, 'opts-a');
        [$otherBrand, $otherCategory] = $this->seedCatalogPair($other, 'opts-b');

        $this->actingAs($bankdar);
        AdminAccess::flushMemo();

        $page = Livewire::test(ProductPriceStockUpdate::class);
        $brands = $page->instance()->getBrandOptionsProperty();
        $categories = $page->instance()->getCategoryOptionsProperty();

        $this->assertArrayHasKey($ownBrand->id, $brands);
        $this->assertArrayNotHasKey($otherBrand->id, $brands);
        $this->assertArrayHasKey($ownCategory->id, $categories);
        $this->assertArrayNotHasKey($otherCategory->id, $categories);
    }
}
