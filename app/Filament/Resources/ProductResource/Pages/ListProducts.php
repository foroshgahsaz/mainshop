<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\ProductResource;
use App\Support\AdminAccess;
use Filament\Actions;

class ListProducts extends AdminListRecords
{
    protected static string $resource = ProductResource::class;

    protected function extraListHeaderActions(): array
    {
        if (! AdminAccess::canAccessAdminResource(ProductResource::class)) {
            return [];
        }

        return [
            Actions\Action::make('exportExcel')
                ->label('خروجی اکسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('filament.admin.products.export', $this->productExportQueryParameters()))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function productExportQueryParameters(): array
    {
        $filters = $this->tableFilters ?? [];
        $params = [];

        $categoryId = data_get($filters, 'category_id.value');
        if (is_numeric($categoryId)) {
            $params['category_id'] = (string) (int) $categoryId;
        }

        $brandId = data_get($filters, 'brand_id.value');
        if (is_numeric($brandId)) {
            $params['brand_id'] = (string) (int) $brandId;
        }

        foreach (['is_active', 'is_featured', 'is_not_for_sale'] as $key) {
            $value = data_get($filters, "{$key}.value");
            if ($value === true || $value === false || $value === 'true' || $value === 'false' || $value === '1' || $value === '0') {
                $params[$key] = $value === true || $value === 'true' || $value === '1' ? '1' : '0';
            }
        }

        $trashed = data_get($filters, 'trashed.value');
        if (is_string($trashed) && $trashed !== '') {
            $params['trashed'] = $trashed;
        }

        $priceMin = data_get($filters, 'price_range.min');
        if (filled($priceMin)) {
            $params['price_min'] = (string) (int) $priceMin;
        }

        $priceMax = data_get($filters, 'price_range.max');
        if (filled($priceMax)) {
            $params['price_max'] = (string) (int) $priceMax;
        }

        return $params;
    }
}