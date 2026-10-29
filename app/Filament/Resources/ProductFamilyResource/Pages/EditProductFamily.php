<?php

namespace App\Filament\Resources\ProductFamilyResource\Pages;

use App\Filament\Resources\ProductFamilyResource;
use App\Filament\Resources\Pages\EditRecord;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Shop\ProductFamilyHomepagePopupService;
use Filament\Actions;

class EditProductFamily extends EditRecord
{
    protected static string $resource = ProductFamilyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->getRecord()->fresh();
        $popup = app(ProductFamilyHomepagePopupService::class);
        $links = $popup->storedLinkItemsForFamily($record);

        $isSidebarFamily = $record->slug === 'chinese-service'
            || $record->name === 'سرویس چینی'
            || (bool) $record->show_on_homepage_sidebar
            || app(HomepageSettingsService::class)->heroSidebarFamilyId() === (int) $record->id;

        if ($isSidebarFamily) {
            if ($links === []) {
                $links = [[
                    'label' => (string) $record->name,
                    'url' => route('products.index', ['product_family_id' => $record->id]),
                    'image' => trim((string) ($record->image ?? '')) ?: null,
                ]];
            }

            app(HomepageSettingsService::class)->syncHeroSidebarFromFamily(
                (int) $record->id,
                (string) $record->name,
                $links
            );
        }

        app(ShopCacheService::class)->forgetHome();
    }
}
