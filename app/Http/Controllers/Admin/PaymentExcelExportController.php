<?php

namespace App\Http\Controllers\Admin;

use App\Filament\Resources\PaymentResource;
use App\Services\Payment\PaymentExcelExportService;
use App\Support\AdminAccess;
use Illuminate\Http\Request;

class PaymentExcelExportController
{
    public function __invoke(Request $request, PaymentExcelExportService $export): mixed
    {
        abort_unless(AdminAccess::canExportFinancialsInAdmin(), 403);

        $query = PaymentResource::getEloquentQuery();
        $query = PaymentResource::applyListTableFiltersToQuery($query, [
            'gateway' => $request->query('gateway'),
            'status' => $request->query('status'),
            'order_status' => $request->query('order_status'),
            'credit_plan_id' => $request->query('credit_plan_id'),
            'gateway_type' => $request->query('gateway_type'),
            'from' => $request->query('paid_from'),
            'until' => $request->query('paid_until'),
        ]);

        return $export->download($query);
    }
}