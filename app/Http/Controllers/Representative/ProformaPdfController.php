<?php

namespace App\Http\Controllers\Representative;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\Pdf\PersianPdfGenerator;
use App\Services\Settings\SettingsService;
use App\Support\AdminAccess;
use App\Support\SalesInvoice\SalesInvoiceBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProformaPdfController extends Controller
{
    public function __invoke(Order $order, Request $request, PersianPdfGenerator $pdf): Response
    {
        $financial = $request->boolean('financial');

        $this->authorizeOrder($order, $financial);

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
        if ($financial) {
            $title .= ' — نسخه مالی';
        }

        $document = SalesInvoiceBuilder::fromOrder($order, $title, $site, financialBreakdown: $financial);

        $filename = ($order->isProforma() ? 'proforma-' : 'invoice-').$order->tracking_code;
        if ($financial) {
            $filename .= '-financial';
        }
        $filename .= '.pdf';

        return $pdf->download('pdf.sales-invoice', [
            'document' => $document,
        ], $filename);
    }

    private function authorizeOrder(Order $order, bool $financial): void
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        if ($financial && ! $this->userCanDownloadFromAdminPanel($user)) {
            abort(403);
        }

        if ($this->userCanDownloadFromAdminPanel($user)) {
            if ($order->items()->exists()) {
                return;
            }

            abort(404);
        }

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

        if ($user->is_representative && (int) $order->representative_id === (int) $user->id) {
            return;
        }

        abort(403);
    }

    private function userCanDownloadFromAdminPanel(User $user): bool
    {
        return AdminAccess::isFullAdmin($user) || AdminAccess::isSalesManagerOnly($user);
    }
}
