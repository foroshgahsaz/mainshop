<x-filament-panels::layout.base :livewire="null">
    <div class="fi-admin-shell" id="fiAdminShell">
        @auth
            @if(auth()->user()->isRepresentative() || auth()->user()->is_admin)
                @include('filament.partials.representative-sidebar')
            @endif
        @endauth

        <div class="main-content" id="mainContent" @auth @else style="margin-right:0" @endauth>
            <div class="content-area">
                <main class="fi-main mx-auto w-full max-w-3xl py-8 px-4">
                    <p class="text-sm text-gray-500 mb-4">پنل نمایندگی — نتیجه پرداخت درگاه</p>
                    @include('payments.partials.result-card', [
                        'payment' => $payment,
                        'order' => $order,
                        'gatewayLabel' => $gatewayLabel,
                        'returnUrl' => $returnUrl,
                        'receiptPdfUrl' => $receiptPdfUrl,
                    ])
                </main>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>

<link rel="stylesheet" href="{{ asset('shop/css/payment-result.css') }}?v={{ filemtime(public_path('shop/css/payment-result.css')) }}">
