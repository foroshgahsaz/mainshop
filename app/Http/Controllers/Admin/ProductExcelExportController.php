<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\ProductResource;
use App\Services\Product\ProductExcelExportService;
use App\Support\AdminAccess;
use Illuminate\Http\Request;

class ProductExcelExportController
{
    public function __invoke(Request $request, ProductExcelExportService $export): mixed
    {
        abort_unless(AdminAccess::canAccessAdminResource(ProductResource::class), 403);

        $query = ProductResource::getEloquentQuery()->withoutEagerLoads();
        $query = ProductResource::applyListTableFiltersToQuery($query, [
            'category_id' => $request->query('category_id'),
            'brand_id' => $request->query('brand_id'),
            'is_active' => $request->query('is_active'),
            'is_featured' => $request->query('is_featured'),
            'is_not_for_sale' => $request->query('is_not_for_sale'),
            'trashed' => $request->query('trashed'),
            'price_min' => $request->query('price_min'),
            'price_max' => $request->query('price_max'),
        ]);

        return $export->download($query);
    }
}