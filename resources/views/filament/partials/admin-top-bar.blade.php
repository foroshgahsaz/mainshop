@php
    use App\Filament\Resources\UserResource;

    $user = filament()->auth()->user();
    $profileUrl = filament()->getUrl();
    if ($user !== null && UserResource::canEdit($user)) {
        $profileUrl = UserResource::getUrl('edit', ['record' => $user], panel: 'admin');
    }
@endphp

<div class="admin-top-bar"
     role="banner"
     x-data
     x-init="$wire.setCurrentPath(window.location.pathname)"
     @livewire:navigated.window="$wire.setCurrentPath(window.location.pathname)">
    <div class="admin-top-bar__primary">
        @if (! empty($adminTopBarCreate['url'] ?? null))
            <a href="{{ $adminTopBarCreate['url'] }}"
               class="admin-top-bar__create-btn">
                <i class="fas fa-plus" aria-hidden="true"></i>
                {{ $adminTopBarCreate['label'] }}
            </a>
        @endif
    </div>

    <div class="admin-top-bar__spacer" aria-hidden="true"></div>

    <div class="admin-top-bar__utilities">
        <a href="{{ $profileUrl }}"
           class="admin-top-bar__icon-btn admin-top-bar__icon-btn--profile"
           title="حساب کاربری"
           aria-label="حساب کاربری">
            <i class="far fa-user" aria-hidden="true"></i>
        </a>
        <button type="button"
                class="admin-top-bar__icon-btn"
                aria-label="اعلان‌ها"
                title="اعلان‌ها"
                disabled>
            <i class="far fa-bell" aria-hidden="true"></i>
        </button>
        <span class="admin-top-bar__vsep" aria-hidden="true"></span>
        <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="admin-top-bar__logout-form">
            @csrf
            <button type="submit"
                    class="admin-top-bar__icon-btn admin-top-bar__icon-btn--logout"
                    title="خروج"
                    aria-label="خروج">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
            </button>
        </form>
        <a href="{{ url('/') }}"
           target="_blank"
           rel="noopener"
           class="fi-admin-global-btn fi-admin-global-btn-store fi-admin-global-btn-store--outline">
            <i class="fas fa-external-link-alt" aria-hidden="true"></i>
            مشاهده فروشگاه
        </a>
    </div>
</div>
