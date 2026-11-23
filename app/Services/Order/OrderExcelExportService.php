<?php

namespace App\Services\Order;

use App\Models\Order;
use App\Support\ShopLabels;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OrderExcelExportService
{
    /** @return list<string> */
    public function headerLabels(): array
    {
        return [
            'کد سفارش',
            'تاریخ',
            'نام مشتری',
            'موبایل مشتری',
            'نماینده',
            'مبلغ نهایی (تومان)',
            'روش پرداخت',
            'وضعیت',
            'حذف‌شده',
        ];
    }

    /**
     * @return list<string|int|null>
     */
    public function rowValues(Order $order): array
    {
        $user = $order->user;

        return [
            (string) ($order->tracking_code ?? ''),
            shop_jalali($order->created_at),
            (string) ($user?->name ?? ''),
            (string) ($user?->phone ?? ''),
            $order->representativeLabel(),
            (int) ($order->final_amount ?? 0),
            ShopLabels::orderPaymentMethodLabel($order),
            ShopLabels::orderStatus($order->status),
            $order->trashed() ? 'بله' : 'خیر',
        ];
    }

    public function download(Builder $query, string $basename = 'orders'): StreamedResponse
    {
        $filename = $basename.'-'.now()->format('Y-m-d-His').'.xlsx';

        return response()->streamDownload(function () use ($query): void {
            $writer = WriterFactory::createFromFile('orders-export.xlsx');
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues($this->headerLabels()));

            (clone $query)
                ->reorder()
                ->orderBy('id')
                ->chunkById(250, function ($orders) use ($writer): void {
                    foreach ($orders as $order) {
                        if (! $order instanceof Order) {
                            continue;
                        }

                        $writer->addRow(Row::fromValues($this->rowValues($order)));
                    }
                });

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}