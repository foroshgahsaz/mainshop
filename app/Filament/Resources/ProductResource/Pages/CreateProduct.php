<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Filament\Resources\Pages\EditRecord;
use Filament\Actions;
use Illuminate\Support\Str;

class CreateProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected static ?string $title = 'افزودن محصول';

    protected static bool $canCreateAnother = false;

    public function mount(int|string|null $record = null): void
    {
        $categoryId = Category::query()->where('is_active', true)->value('id')
            ?? Category::query()->value('id');

        if (! $categoryId) {
            $categoryId = Category::query()->create([
                'name' => 'عمومی',
                'slug' => 'general',
                'is_active' => true,
            ])->id;
        }

        $this->record = Product::query()->create([
            'name' => '',
            'slug' => 'draft-'.Str::random(10),
            'price' => 0,
            'stock' => 0,
            'category_id' => $categoryId,
            'is_active' => false,
        ]);

        $this->authorizeAccess();
        $this->fillForm();
        $this->previousUrl = url()->previous();
    }

    public function hasCombinedRelationManagerTabsWithContentForm(): bool
    {
        return true;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('حذف پیش‌نویس')
                ->successNotificationTitle('حذف شد'),
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        return ProductResource::getUrl('edit', ['record' => $this->record]);
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'ذخیره شد';
    }
}
