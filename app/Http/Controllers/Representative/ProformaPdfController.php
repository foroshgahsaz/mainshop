<?php

namespace App\Http\Controllers\Representative;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Settings\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ProformaPdfController extends Controller
{
    public function __invoke(Order $order): Response
    {
        $this->authorizeOrder($order);

        $order->load([
            'items',
            'user',
            'address.province',
            'address.city',
            'freightCarrier.province',
            'freightCarrier.city',
            'representative',
        ]);

        $site = app(SettingsService::class)->site();

        $pdf = Pdf::loadView('pdf.representative-proforma', [
            'order' => $order,
            'siteName' => (string) ($site['name'] ?? config('app.name')),
        ])->setPaper('a4');

        $filename = 'proforma-'.$order->tracking_code.'.pdf';

        return $pdf->download($filename);
    }

    private function authorizeOrder(Order $order): void
    {
        if (! $order->isRepresentativeOrder()) {
            abort(404);
        }

        if (! $order->isDraft() && ! $order->isProforma()) {
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
