@extends('layouts.representative-payment-result')

@section('content')
    <p class="text-sm text-gray-600 mb-4">نتیجه پرداخت درگاه برای پیش‌فاکتور نمایندگی</p>
    @include('payments.partials.result-card', [
        'payment' => $payment,
        'order' => $order,
        'gatewayLabel' => $gatewayLabel,
        'returnUrl' => $returnUrl,
        'receiptPdfUrl' => $receiptPdfUrl,
    ])
@endsection
