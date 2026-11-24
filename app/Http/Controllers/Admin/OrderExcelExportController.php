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
        abort_unless(AdminAccess::canExportFinancialsInAdmin() && AdminAccess::canAccessAdminResource(OrderResource::class), 403);

        $query = OrderResource::getEloquentQuery();
        $query = OrderResource::applyListTableFiltersToQuery($query, [
            'status' => $request->query('status'),
            'payment_gateway' => $request->query('payment_gateway'),
            'trashed' => $request->query('trashed'),
            'credit_plan_id' => $request->query('credit_plan_id'),
            'payment_gateway_type' => $request->query('payment_gateway_type'),
            'has_successful_payment' => $request->query('has_successful_payment'),
            'created_from' => $request->query('created_from'),
            'created_until' => $request->query('created_until'),
        ]);

        return $export->download($query);
    }
}