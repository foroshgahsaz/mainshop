<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\Pages\AdminListRecords;
use App\Models\Order;
use App\Support\AdminAccess;
use Filament\Actions;

class ListOrders extends AdminListRecords
{
    protected static string $resource = OrderResource::class;

    public function mount(): void
    {
        parent::mount();

        if (! AdminAccess::isAccountantOnly()) {
            return;
        }

        $this->tableFilters = array_replace_recursive($this->tableFilters ?? [], [
            'status' => ['value' => Order::STATUS_PROCESSING],
            'has_successful_payment' => ['value' => true],
        ]);
    }

    protected function adminListIcon(): ?string
    {
        return 'fa-shopping-bag';
    }

    protected function extraListHeaderActions(): array
    {
        $actions = [];

        if (AdminAccess::canExportFinancialsInAdmin()) {
            $actions[] = Actions\Action::make('exportExcel')
                ->label('خروجی اکسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('filament.admin.orders.export', $this->orderExportQueryParameters()))
                ->openUrlInNewTab();
        }

        if (AdminAccess::canCreateOrdersInAdmin()) {
            $actions[] = Actions\CreateAction::make()
                ->label('ایجاد سفارش')
                ->icon('heroicon-o-plus');
        }

        return $actions;
    }

    /**
     * @return array<string, string>
     */
    protected function orderExportQueryParameters(): array
    {
        $filters = $this->tableFilters ?? [];
        $params = [];

        $status = data_get($filters, 'status.value');
        if (is_string($status) && $status !== '') {
            $params['status'] = $status;
        }

        $gateway = data_get($filters, 'payment_gateway.value');
        if (is_string($gateway) && $gateway !== '') {
            $params['payment_gateway'] = $gateway;
        }

        $trashed = data_get($filters, 'trashed.value');
        if (is_string($trashed) && $trashed !== '') {
            $params['trashed'] = $trashed;
        }

        $creditPlan = data_get($filters, 'credit_plan_id.value');
        if (filled($creditPlan)) {
            $params['credit_plan_id'] = (string) $creditPlan;
        }

        $gatewayType = data_get($filters, 'payment_gateway_type.value');
        if (is_string($gatewayType) && $gatewayType !== '') {
            $params['payment_gateway_type'] = $gatewayType;
        }

        $hasSuccess = data_get($filters, 'has_successful_payment.value');
        if ($hasSuccess === true || $hasSuccess === 1 || $hasSuccess === '1') {
            $params['has_successful_payment'] = '1';
        } elseif ($hasSuccess === false || $hasSuccess === 0 || $hasSuccess === '0') {
            $params['has_successful_payment'] = '0';
        }

        $from = data_get($filters, 'created_at_range.from');
        if (filled($from)) {
            $params['created_from'] = (string) $from;
        }

        $until = data_get($filters, 'created_at_range.until');
        if (filled($until)) {
            $params['created_until'] = (string) $until;
        }

        return $params;
    }
}
