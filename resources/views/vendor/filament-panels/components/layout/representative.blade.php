@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="fi-admin-shell" id="fiAdminShell">
        @include('filament.partials.representative-sidebar')

        <div class="sidebar-backdrop" id="sidebarBackdrop" hidden aria-hidden="true"></div>

        <div class="main-content" id="mainContent">
            <div class="content-area">
                <main class="fi-main mx-auto w-full">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>
