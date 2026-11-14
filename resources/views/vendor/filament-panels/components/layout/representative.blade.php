@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="fi-admin-shell fi-admin-shell--representative" id="fiAdminShell">
        @include('filament.partials.representative-sidebar')

        <div class="sidebar-backdrop" id="sidebarBackdrop" hidden aria-hidden="true"></div>

        <div class="main-content" id="mainContent">
            {{-- داخل main-content تا زیر سایدبار ثابت نرود --}}
            @if (session()->has(\App\Services\Auth\AdminImpersonationService::SESSION_ACTOR_ID))
                @include('filament.hooks.impersonation-banner')
            @endif
            <div class="content-area">
                <main class="fi-main mx-auto w-full">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>
