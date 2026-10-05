<?php

namespace App\Http\Controllers\Representative;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Pdf\PersianPdfGenerator;
use App\Support\Payment\PaymentReceiptPresenter;
use Illuminate\Http\Response;

class PaymentReceiptPdfController extends Controller
{
    public function __invoke(Payment $payment, PersianPdfGenerator $pdf): Response
    {
        abort_unless($payment->status === Payment::STATUS_SUCCESS, 404);
        abort_unless(
            $payment->wasPaidByRepresentative() && $payment->order?->isRepresentativeOrder(),
            404
        );

        $user = auth()->user();
        if ($user !== null) {
            $order = $payment->order;
            if ($user->is_admin) {
                // allowed
            } elseif ($user->is_representative && (int) $order?->representative_id === (int) $user->id) {
                // allowed
            } elseif ((int) $payment->user_id === (int) $user->id) {
                // allowed
            } else {
                abort(403);
            }
        }

        $data = PaymentReceiptPresenter::for($payment);
        $filename = 'payment-receipt-'.$payment->tracking_code.'.pdf';

        return $pdf->download('pdf.payment-receipt', [
            'receipt' => $data,
        ], $filename);
    }
}
