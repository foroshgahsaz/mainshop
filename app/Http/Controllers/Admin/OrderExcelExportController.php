<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\OrderResource;
use App\Services\Order\OrderExcelExportService;
use App\Support\AdminAccess;
use Illuminate\Http\Request;

class OrderExcelExportController
{
    public function __invoke(Request $request, OrderExcelExportService $export): mixed
    {
        abort_unless(AdminAccess::canAccessAdminResource(OrderResource::class), 403);

        $query = OrderResource::getEloquentQuery();
        $query = OrderResource::applyListTableFiltersToQuery($query, [
            'status' => $request->query('status'),
            'payment_gateway' => $request->query('payment_gateway'),
            'trashed' => $request->query('trashed'),
        ]);

        return $export->download($query);
    }
}