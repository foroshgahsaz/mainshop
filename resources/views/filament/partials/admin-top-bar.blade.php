@php
    use App\Filament\Resources\UserResource;

    $user = filament()->auth()->user();
    $profileUrl = $user
        ? UserResource::getUrl('edit', ['record' => $user->id], panel: 'admin')
        : filament()->getUrl();
    $initial = mb_substr($user?->name ?? 'ا', 0, 1);
@endphp

<div class="admin-top-bar" role="banner">
    <div class="admin-top-bar__spacer" aria-hidden="true"></div>
    <div class="admin-top-bar__utilities">
        <a href="{{ $profileUrl }}"
           class="admin-top-bar__avatar"
           title="پروفایل"
           aria-label="پروفایل کاربر">{{ $initial }}</a>
        <button type="button"
                class="admin-top-bar__icon-btn"
                aria-label="اعلان‌ها"
                title="اعلان‌ها"
                disabled>
            <i class="far fa-bell"></i>
        </button>
        <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="admin-top-bar__logout-form">
            @csrf
            <button type="submit" class="fi-admin-global-btn fi-admin-global-btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                خروج
            </button>
        </form>
        <a href="{{ url('/') }}"
           target="_blank"
           rel="noopener"
           class="fi-admin-global-btn fi-admin-global-btn-store fi-admin-global-btn-store--outline">
            <i class="fas fa-external-link-alt"></i>
            مشاهده فروشگاه
        </a>
    </div>
</div>
