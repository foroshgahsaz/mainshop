<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Product;
use App\Services\Product\ProductPriceStockUpdateService;
use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

class ProductPriceStockUpdate extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'بروزرسانی قیمت';

    protected static ?string $slug = 'product-price-stock-update';

    protected static ?string $title = 'بروزرسانی قیمت و موجودی';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.product-price-stock-update';

    protected string $paginationTheme = 'tailwind';

    public ?int $filterCategoryId = null;

    /** @var ''|'all'|'in_stock'|'out_of_stock' */
    public string $filterStock = 'all';

    /** @var array<int, int|string> */
    public array $rowPrices = [];

    /** @var array<int, int|string> */
    public array $rowStocks = [];

    public string $bulkPricePercent = '';

    public string $bulkStockDelta = '';

    public static function canAccess(): bool
    {
        return AdminAccess::canManageProductsInAdmin();
    }

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatedFilterCategoryId(): void
    {
        $this->resetPage();
        $this->resetRowInputs();
    }

    public function updatedFilterStock(): void
    {
        $this->resetPage();
        $this->resetRowInputs();
    }

    /** @return LengthAwarePaginator<int, Product> */
    public function getProductsProperty(): LengthAwarePaginator
    {
        return $this->filteredQuery()
            ->paginate(25);
    }

    /** @return array<int, string> */
    public function getCategoryOptionsProperty(): array
    {
        return Category::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public function saveProduct(int $productId, ProductPriceStockUpdateService $service): void
    {
        $this->validate([
            "rowPrices.{$productId}" => ['required', 'integer', 'min:0'],
            "rowStocks.{$productId}" => ['required', 'integer', 'min:0', 'max:999999'],
        ], [], [
            "rowPrices.{$productId}" => 'قیمت',
            "rowStocks.{$productId}" => 'موجودی',
        ]);

        $product = Product::query()->whereKey($productId)->firstOrFail();

        $service->updateRow(
            $product,
            (int) $this->rowPrices[$productId],
            (int) $this->rowStocks[$productId],
            auth()->id(),
        );

        Notification::make()
            ->title('ذخیره شد')
            ->body('قیمت و موجودی «'.$product->name.'» به‌روزرسانی شد.')
            ->success()
            ->send();
    }

    public function applyBulkAdjustments(ProductPriceStockUpdateService $service): void
    {
        $hasPrice = trim($this->bulkPricePercent) !== '';
        $hasStock = trim($this->bulkStockDelta) !== '';

        if (! $hasPrice && ! $hasStock) {
            Notification::make()
                ->title('ورودی خالی')
                ->body('درصد تغییر قیمت یا مقدار تغییر موجودی را وارد کنید.')
                ->warning()
                ->send();

            return;
        }

        $rules = [];
        if ($hasPrice) {
            $rules['bulkPricePercent'] = ['required', 'numeric', 'not_in:0', 'between:-99,1000'];
        }
        if ($hasStock) {
            $rules['bulkStockDelta'] = ['required', 'integer', 'not_in:0', 'between:-999999,999999'];
        }

        $this->validate($rules, [], [
            'bulkPricePercent' => 'درصد تغییر قیمت',
            'bulkStockDelta' => 'تغییر موجودی',
        ]);

        $pricePercent = $hasPrice ? (float) $this->bulkPricePercent : null;
        $stockDelta = $hasStock ? (int) $this->bulkStockDelta : null;

        $count = (clone $this->filteredQuery())->count();

        if ($count === 0) {
            Notification::make()->title('محصولی یافت نشد')->warning()->send();

            return;
        }

        $result = $service->bulkAdjustOnQuery(
            $this->filteredQuery(),
            $pricePercent,
            $stockDelta,
            auth()->id(),
        );

        $this->bulkPricePercent = '';
        $this->bulkStockDelta = '';
        $this->resetRowInputs();
        $this->resetPage();

        Notification::make()
            ->title('اعمال گروهی انجام شد')
            ->body(sprintf(
                'روی %d محصول فیلترشده — قیمت: %d، موجودی: %d',
                $count,
                $result['price_updated'],
                $result['stock_updated'],
            ))
            ->success()
            ->send();
    }

    protected function filteredQuery(): Builder
    {
        $query = Product::query()
            ->with(['category:id,name', 'lastEditedBy:id,name'])
            ->orderByDesc('updated_at');

        if ($this->filterCategoryId) {
            $query->where('category_id', $this->filterCategoryId);
        }

        match ($this->filterStock) {
            'in_stock' => $query->where('stock', '>', 0),
            'out_of_stock' => $query->where('stock', '<=', 0),
            default => null,
        };

        return $query;
    }

    protected function resetRowInputs(): void
    {
        $this->rowPrices = [];
        $this->rowStocks = [];
    }

    public function rendering(): void
    {
        foreach ($this->products as $product) {
            if (! array_key_exists($product->id, $this->rowPrices)) {
                $this->rowPrices[$product->id] = (int) $product->price;
            }
            if (! array_key_exists($product->id, $this->rowStocks)) {
                $this->rowStocks[$product->id] = (int) $product->stock;
            }
        }
    }
}
