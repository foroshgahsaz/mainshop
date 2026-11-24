<?php

namespace App\Services\Payment;

use App\Filament\Support\FinancialTableFilters;
use App\Models\Payment;
use App\Support\ShopLabels;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PaymentExcelExportService
{
    /** @return list<string> */
    public function headerLabels(): array
    {
        return [
            'کد پیگیری پرداخت',
            'کد سفارش',
            'تاریخ پرداخت',
            'نام مشتری',
            'موبایل مشتری',
            'مبلغ (تومان)',
            'درگاه',
            'نوع (اعتباری/نقدی)',
            'طرح اعتباری',
            'وضعیت',
            'کد مرجع',
        ];
    }

    /**
     * @return list<string|int|null>
     */
    public function rowValues(Payment $payment): array
    {
        $payment->loadMissing(['user', 'order.creditPlan']);

        return [
            (string) $payment->tracking_code,
            (string) ($payment->order?->tracking_code ?? ''),
            $payment->paid_at ? shop_jalali($payment->paid_at) : shop_jalali($payment->created_at),
            (string) ($payment->user?->name ?? ''),
            (string) ($payment->user?->phone ?? ''),
            (int) $payment->amount,
            ShopLabels::gateway((string) $payment->gateway),
            FinancialTableFilters::gatewayTypeLabel((string) $payment->gateway),
            (string) ($payment->order?->creditPlan?->name ?? ''),
            ShopLabels::paymentStatus($payment->status),
            (string) ($payment->gatewayReference() ?? ''),
        ];
    }

    public function download(Builder $query, string $basename = 'payments'): StreamedResponse
    {
        $filename = $basename.'-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($query): void {
            $writer = WriterFactory::createFromFile('payments-export.xlsx');
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->headerLabels()));

            (clone $query)
                ->reorder()
                ->orderBy('id')
                ->chunkById(250, function ($payments) use ($writer): void {
                    foreach ($payments as $payment) {
                        if (! $payment instanceof Payment) {
                            continue;
                        }

                        $writer->addRow(Row::fromValues($this->rowValues($payment)));
                    }
                });

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}