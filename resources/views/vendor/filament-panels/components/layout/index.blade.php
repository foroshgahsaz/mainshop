@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="fi-admin-shell" id="fiAdminShell">
        @include('filament.partials.admin-sidebar')

        <div class="main-content" id="mainContent">
            <div class="content-area">
                <main class="fi-main mx-auto w-full">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>
