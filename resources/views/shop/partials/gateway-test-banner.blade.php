@php
    $user = auth()->user();
@endphp
@if ($user && $user->canShopGatewayTest() && ! \App\Support\ShopStaffPreview::viewerIsStaff())
    <div class="gateway-test-banner" role="status">
        حالت <strong>تست درگاه</strong> — محصولات «نمایش برای مدیر» را می‌بینید؛ مشتریان عادی این‌ها را نمی‌بینند.
    </div>
@endif
