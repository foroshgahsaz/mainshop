@php
    $profileRequired = auth()->check() && \App\Support\UserDisplayName::isPlaceholder(auth()->user()->name);
@endphp
<!-- LOGIN MODAL -->
<div id="loginModal"
     style="z-index:120"
     @if ($profileRequired) data-profile-required @endif
     class="fixed inset-0 transition-all duration-300 flex items-center justify-center p-4 {{ $profileRequired ? 'bg-black/60 backdrop-blur-sm' : 'invisible pointer-events-none' }}">
    <div class="absolute inset-0 pointer-events-auto bg-black/0"
         onclick="window.requestCloseLoginModal?.()"
         aria-hidden="true"></div>
    <div data-modal-inner
         class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden relative z-10 transition-all duration-300 {{ $profileRequired ? 'scale-100 opacity-100' : 'scale-95 opacity-0' }}"
         onclick="event.stopPropagation()">
        @livewire('auth.login-modal')
    </div>
</div>
