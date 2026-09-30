<?php

namespace App\Http\Controllers\Representative;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pdf\PersianPdfGenerator;
use App\Services\Settings\SettingsService;
use App\Support\SalesInvoice\SalesInvoiceBuilder;
use Illuminate\Http\Response;

class ProformaPdfController extends Controller
{
    public function __invoke(Order $order, PersianPdfGenerator $pdf): Response
    {
        $this->authorizeOrder($order);

        $order->load([
            'items',
            'user',
            'address.provinceModel',
            'address.cityModel',
            'freightCarrier.province',
            'freightCarrier.city',
            'representative',
        ]);

        $site = app(SettingsService::class)->site();
        $title = $order->isProforma() ? 'پیش‌فاکتور' : 'فاکتور فروش';

        $document = SalesInvoiceBuilder::fromOrder($order, $title, $site);

        $filename = ($order->isProforma() ? 'proforma-' : 'invoice-').$order->tracking_code.'.pdf';

        return $pdf->download('pdf.sales-invoice', [
            'document' => $document,
        ], $filename);
    }

    private function authorizeOrder(Order $order): void
    {
        if (! $order->isRepresentativeOrder()) {
            abort(404);
        }

        $allowedStatuses = [
            Order::STATUS_DRAFT,
            Order::STATUS_PROFORMA,
            Order::STATUS_PROCESSING,
            Order::STATUS_PENDING,
        ];

        if (! in_array($order->status, $allowedStatuses, true)) {
            abort(404);
        }

        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->is_admin) {
            return;
        }

        if ($user->is_representative && (int) $order->representative_id === (int) $user->id) {
            return;
        }

        abort(403);
    }
}
