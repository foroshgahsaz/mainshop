@extends('layouts.shop')

@section('title', 'نتیجه پرداخت — '.site_name())

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-2xl">
        @include('payments.partials.result-card', [
            'payment' => $payment,
            'order' => $order,
            'gatewayLabel' => $gatewayLabel,
            'returnUrl' => $returnUrl,
            'receiptPdfUrl' => $receiptPdfUrl ?? null,
        ])
    </div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('shop/css/payment-result.css') }}?v={{ filemtime(public_path('shop/css/payment-result.css')) }}">
@endpush
