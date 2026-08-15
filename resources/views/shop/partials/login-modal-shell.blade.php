<!-- LOGIN MODAL -->
<div id="loginModal"
     style="z-index:120"
     class="fixed inset-0 invisible pointer-events-none transition-all duration-300 flex items-center justify-center p-4">
    <div class="absolute inset-0 pointer-events-auto bg-black/0"
         onclick="toggleElement('loginModal', false)"
         aria-hidden="true"></div>
    <div data-modal-inner
         class="bg-white w-full max-w-md rounded-2xl shadow-xl overflow-hidden relative z-10 scale-95 opacity-0 transition-all duration-300"
         onclick="event.stopPropagation()">
        @livewire('auth.login-modal')
    </div>
</div>
