<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\ProductResource;
use App\Filament\Support\ProductDeleteActions;
use App\Filament\Support\RecordsProductLastEditor;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\AdminAccess;
use Illuminate\Support\Str;

class CreateProduct extends EditRecord
{
    use RecordsProductLastEditor;

    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'افزودن محصول';

    protected static bool $canCreateAnother = false;

    public function mount(int|string|null $record = null): void
    {
        [$categoryId, $brandId] = $this->resolveDraftCatalogIds();

        $creatorId = auth()->id();

        $this->record = Product::query()->create([
            'name' => '',
            'slug' => 'draft-'.Str::random(10),
            'price' => 0,
            'stock' => 0,
            'category_id' => $categoryId,
            'brand_id' => $brandId,
            'is_active' => false,
            'robots' => 'index,follow',
            'created_by_user_id' => $creatorId,
            'last_edited_by_user_id' => $creatorId,
        ]);

        $this->authorizeAccess();
        $this->fillForm();
        $this->previousUrl = url()->previous();
    }

    /**
     * @return array{0: int, 1: int|null}
     */
    protected function resolveDraftCatalogIds(): array
    {
        if (AdminAccess::isBankdarOnly()) {
            $bankdarId = (int) auth()->id();
            $categoryId = Category::query()
                ->where('bankdar_id', $bankdarId)
                ->where('is_active', true)
                ->value('id')
                ?? Category::query()->where('bankdar_id', $bankdarId)->value('id');
            $brandId = Brand::query()
                ->where('bankdar_id', $bankdarId)
                ->value('id');

            abort_unless(
                $categoryId,
                403,
                'دسته‌ای به شما اختصاص داده نشده. از مدیر سیستم بخواهید حداقل یک دسته برای بنکدار تنظیم کند.'
            );

            return [(int) $categoryId, $brandId ? (int) $brandId : null];
        }

        $categoryId = Category::query()->where('is_active', true)->value('id')
            ?? Category::query()->value('id');

        if (! $categoryId) {
            $categoryId = Category::query()->create([
                'name' => 'عمومی',
                'slug' => 'general',
                'is_active' => true,
            ])->id;
        }

        return [(int) $categoryId, null];
    }

    public function hasCombinedRelationManagerTabsWithContentForm(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            ProductDeleteActions::pageDeleteAction('حذف پیش‌نویس'),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        $this->record->refresh();

        return ProductResource::getUrl('edit', ['record' => $this->record->getKey()]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'ذخیره شد';
    }
}
