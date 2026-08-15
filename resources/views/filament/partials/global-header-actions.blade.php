<div class="fi-admin-global-actions flex flex-wrap items-center gap-2">
    <a href="{{ url('/') }}"
       target="_blank"
       rel="noopener"
       class="fi-admin-global-btn fi-admin-global-btn-store">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3"/>
        </svg>
        مشاهده فروشگاه
    </a>
    <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="inline">
        @csrf
        <button type="submit" class="fi-admin-global-btn fi-admin-global-btn-logout">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
            </svg>
            خروج
        </button>
    </form>
</div>
