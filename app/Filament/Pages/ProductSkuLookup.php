<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Services\Catalog\BankdarCatalogService;
use App\Services\Product\ProductDeletionService;
use App\Services\Product\ProductSkuLookupService;
use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProductSkuLookup extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationLabel = 'جستجوی کد کالا';

    protected static ?string $slug = 'product-sku-lookup';

    protected static ?string $title = 'جستجوی کد کالا (SKU)';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.product-sku-lookup';

    public string $sku = '';

    /** @var list<array<string, mixed>> */
    public array $results = [];

    public bool $searched = false;

    public static function canAccess(): bool
    {
        return AdminAccess::canManageProductsInAdmin();
    }

    public function search(ProductSkuLookupService $lookup): void
    {
        $this->validate([
            'sku' => ['required', 'string', 'min:3', 'max:255'],
        ], [], [
            'sku' => 'کد کالا',
        ]);

        $this->results = $lookup->find($this->sku, auth()->user());
        $this->searched = true;
    }

    public function canEditProduct(int $productId): bool
    {
        $user = auth()->user();
        if ($user === null) {
            return false;
        }

        if (AdminAccess::isFullAdmin($user) || AdminAccess::isSalesManagerOnly($user)) {
            return true;
        }

        if (! AdminAccess::isBankdarOnly($user)) {
            return false;
        }

        $product = ProductResource::getEloquentQuery()->whereKey($productId)->first();

        return $product !== null
            && app(BankdarCatalogService::class)->productBelongsToBankdar($product, (int) $user->id);
    }

    public function editUrl(int $productId): string
    {
        return ProductResource::getUrl('edit', ['record' => $productId]);
    }

    public function canManageDeletedProduct(): bool
    {
        return AdminAccess::isFullAdmin();
    }

    public function restoreProduct(int $productId, ProductDeletionService $deletion, ProductSkuLookupService $lookup): void
    {
        $product = Product::query()->withTrashed()->whereKey($productId)->first();

        if ($product === null) {
            Notification::make()->title('محصول یافت نشد')->danger()->send();

            return;
        }

        try {
            $deletion->restore($product);
            Notification::make()->title('محصول بازگردانی شد')->success()->send();
        } catch (\RuntimeException $e) {
            Notification::make()->title('بازگردانی ممکن نیست')->body($e->getMessage())->danger()->send();

            return;
        }

        if ($this->searched && $this->sku !== '') {
            $this->results = $lookup->find($this->sku, auth()->user());
        }
    }

    public function forceDeleteProduct(int $productId, ProductDeletionService $deletion, ProductSkuLookupService $lookup): void
    {
        $product = Product::query()->withTrashed()->whereKey($productId)->first();

        if ($product === null) {
            Notification::make()->title('محصول یافت نشد')->danger()->send();

            return;
        }

        try {
            $deletion->forceDeleteForever($product);
            Notification::make()->title('محصول برای همیشه حذف شد')->success()->send();
        } catch (\RuntimeException $e) {
            Notification::make()->title('حذف دائمی ممکن نیست')->body($e->getMessage())->danger()->send();

            return;
        }

        if ($this->searched && $this->sku !== '') {
            $this->results = $lookup->find($this->sku, auth()->user());
        }
    }
}