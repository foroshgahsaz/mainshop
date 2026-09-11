@props([
    'redirect' => null,
    'class' => '',
    'label' => 'ورود / ثبت‌نام',
])

<button type="button"
        data-open-login
        @if ($redirect) data-login-redirect="{{ $redirect }}" @endif
        {{ $attributes->merge(['class' => $class]) }}>
    {{ $slot->isEmpty() ? $label : $slot }}
</button>
