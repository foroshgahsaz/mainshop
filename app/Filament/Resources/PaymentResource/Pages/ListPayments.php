<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\Pages\AdminListRecords;
use App\Filament\Resources\PaymentResource;
use App\Support\AdminAccess;
use Filament\Actions;

class ListPayments extends AdminListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function adminListIcon(): ?string
    {
        return 'fa-credit-card';
    }

    protected function extraListHeaderActions(): array
    {
        if (! AdminAccess::canExportFinancialsInAdmin()) {
            return [];
        }

        return [
            Actions\Action::make('exportExcel')
                ->label('خروجی اکسل')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('filament.admin.payments.export', $this->paymentExportQueryParameters()))
                ->openUrlInNewTab(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function paymentExportQueryParameters(): array
    {
        $filters = $this->tableFilters ?? [];
        $params = [];

        foreach ([
            'gateway' => 'gateway',
            'status' => 'status',
            'order_status' => 'order_status',
            'credit_plan_id' => 'credit_plan_id',
            'gateway_type' => 'gateway_type',
        ] as $filterKey => $paramKey) {
            $value = data_get($filters, "{$filterKey}.value");
            if (filled($value)) {
                $params[$paramKey] = (string) $value;
            }
        }

        $from = data_get($filters, 'paid_at_range.from');
        if (filled($from)) {
            $params['paid_from'] = (string) $from;
        }

        $until = data_get($filters, 'paid_at_range.until');
        if (filled($until)) {
            $params['paid_until'] = (string) $until;
        }

        return $params;
    }
}
