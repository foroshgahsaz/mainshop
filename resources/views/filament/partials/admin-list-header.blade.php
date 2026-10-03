@props([
    'icon' => 'fa-users',
    'section' => '',
    'title' => '',
])

<header class="fi-header fi-admin-list-header">
    <div class="fi-admin-list-header__trail">
        <span class="fi-admin-list-header__icon" aria-hidden="true">
            <i class="fas {{ $icon }}"></i>
        </span>
        <span class="fi-admin-list-header__section">{{ $section }}</span>
        <span class="fi-admin-list-header__sep" aria-hidden="true">|</span>
        <span class="fi-admin-list-header__title">{{ $title }}</span>
    </div>
</header>
