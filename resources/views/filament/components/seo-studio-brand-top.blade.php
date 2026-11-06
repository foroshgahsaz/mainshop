@php
    $variant = $variant ?? 'seo';
@endphp
<div class="ss-top">
    <div class="ss-brand">
        <div class="ss-logo" aria-hidden="true">✦</div>
        <div>
            <h2>{{ $variant === 'redirect' ? 'REDIRECT STUDIO' : 'SEO STUDIO' }}</h2>
            <p>{{ $variant === 'redirect' ? 'مدیریت ریدایرکت و آدرس‌های قدیمی' : 'مرکز مدیریت و بهینه‌سازی سئو' }}</p>
        </div>
    </div>
</div>
