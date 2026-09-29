@php
    $livewire ??= null;
@endphp

<x-filament-panels::layout.base :livewire="$livewire">
    <div class="fi-admin-shell" id="fiAdminShell">
        @if (filament()->getId() === 'representative')
            @include('filament.partials.representative-sidebar')
        @else
            @include('filament.partials.admin-sidebar')
        @endif

        <div class="main-content" id="mainContent">
            <div class="content-area">
                <main class="fi-main mx-auto w-full">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </div>
</x-filament-panels::layout.base>
