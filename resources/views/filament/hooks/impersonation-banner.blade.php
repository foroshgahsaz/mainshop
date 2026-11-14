@php
    $ctx = app(\App\Services\Auth\AdminImpersonationService::class)->bannerContext();
@endphp
@if ($ctx)
    @php
        $roleLabel = match ($ctx['target_role']) {
            'sales_manager' => 'مدیر فروش',
            'bankdar' => 'بنکدار',
            'representative' => 'نماینده',
            default => 'کاربر',
        };
    @endphp
    <div class="fi-impersonation-banner" dir="rtl" role="status">
        <div class="fi-impersonation-banner__inner">
            <p class="fi-impersonation-banner__text">
                وارد شده به‌عنوان
                <strong>{{ $roleLabel }} {{ $ctx['target_name'] }}</strong>
                <span class="fi-impersonation-banner__sep">·</span>
                بازگشت به {{ $ctx['actor_name'] }}
            </p>
            <form method="POST" action="{{ route('admin.impersonation.stop') }}" class="fi-impersonation-banner__form">
                @csrf
                <button type="submit" class="fi-impersonation-banner__btn">
                    بازگشت به ادمین
                </button>
            </form>
        </div>
    </div>
    <style>
        /* داخل .main-content رندر می‌شود — هم‌عرض محتوا، نه زیر سایدبار */
        .fi-impersonation-banner {
            position: sticky;
            top: 0;
            z-index: 40;
            background: #b45309;
            color: #fff;
            box-shadow: 0 1px 0 rgba(0,0,0,.08);
            width: 100%;
            box-sizing: border-box;
        }
        .fi-impersonation-banner__inner {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem 1rem;
            padding: .65rem 1.25rem;
        }
        .fi-impersonation-banner__text {
            margin: 0;
            font-size: .9rem;
            line-height: 1.45;
            flex: 1 1 14rem;
            min-width: 0;
            overflow-wrap: anywhere;
        }
        .fi-impersonation-banner__sep { opacity: .7; margin: 0 .35rem; }
        .fi-impersonation-banner__form {
            flex: 0 0 auto;
        }
        .fi-impersonation-banner__btn {
            appearance: none;
            border: 1px solid rgba(255,255,255,.45);
            background: rgba(255,255,255,.12);
            color: #fff;
            font-weight: 700;
            font-size: .82rem;
            border-radius: .55rem;
            padding: .4rem .85rem;
            cursor: pointer;
            white-space: nowrap;
        }
        .fi-impersonation-banner__btn:hover {
            background: rgba(255,255,255,.22);
        }
    </style>
@endif
