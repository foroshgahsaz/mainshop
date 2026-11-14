<x-filament-panels::page>
    @php
        $salesManager = $tree['sales_manager'] ?? null;
        $bankdarNode = $tree['bankdar'] ?? null;
        $reps = $tree['representatives'] ?? [];
        $repCount = count($reps);
        $customerTotal = collect($reps)->sum(fn ($r) => (int) ($r['customers_count'] ?? 0));
        $roleLabels = [
            'sales_manager' => 'مدیر فروش',
            'bankdar' => 'بنکدار',
            'representative' => 'نماینده',
        ];
        $isBankdarViewer = \App\Support\AdminAccess::isBankdarOnly();
    @endphp

    <div class="bst" dir="rtl">
        <header class="bst__hero">
            <div class="bst__hero-text">
                <p class="bst__kicker">ساختار فروش</p>
                <h2 class="bst__title">{{ $bankdarNode['name'] ?? 'بنکدار' }}</h2>
                <p class="bst__intro">
                    @if ($isBankdarViewer)
                        نمایندگان و مشتریان زیرمجموعهٔ شما در یک نگاه.
                    @else
                        سلسله‌مراتب از مدیر فروش تا نمایندگان این بنکدار.
                    @endif
                </p>
            </div>
            <div class="bst__stats" role="list">
                <div class="bst__stat" role="listitem">
                    <span class="bst__stat-value">{{ number_format($repCount) }}</span>
                    <span class="bst__stat-label">نماینده</span>
                </div>
                <div class="bst__stat" role="listitem">
                    <span class="bst__stat-value">{{ number_format($customerTotal) }}</span>
                    <span class="bst__stat-label">مشتری زیرمجموعه</span>
                </div>
            </div>
        </header>

        <ol class="bst__tree">
            @unless ($isBankdarViewer)
                <li class="bst__level bst__level--sm">
                    <div class="bst__rail" aria-hidden="true"><span class="bst__dot"></span></div>
                    <div class="bst__content">
                        @if ($salesManager)
                            @include('filament.pages.partials.bankdar-subtree-card', [
                                'node' => $salesManager,
                                'roleLabel' => $roleLabels['sales_manager'],
                            ])
                        @else
                            <article class="bst-card bst-card--empty">
                                <div class="bst-card__avatar">—</div>
                                <div class="bst-card__body">
                                    <span class="bst-card__role">مدیر فروش</span>
                                    <span class="bst-card__name">تعیین نشده</span>
                                    <p class="bst-card__hint">برای این بنکدار مدیر فروش ثبت نشده است.</p>
                                </div>
                            </article>
                        @endif
                    </div>
                </li>
            @endunless

            <li class="bst__level bst__level--bankdar">
                <div class="bst__rail" aria-hidden="true"><span class="bst__dot"></span></div>
                <div class="bst__content">
                    @if ($bankdarNode)
                        @include('filament.pages.partials.bankdar-subtree-card', [
                            'node' => $bankdarNode,
                            'roleLabel' => $roleLabels['bankdar'],
                        ])
                    @endif
                </div>
            </li>

            <li class="bst__level bst__level--reps">
                <div class="bst__rail" aria-hidden="true"><span class="bst__dot"></span></div>
                <div class="bst__content">
                    <div class="bst__section-label">
                        <span>نمایندگان</span>
                        <span class="bst__section-count">{{ number_format($repCount) }} نفر</span>
                    </div>

                    @if ($repCount === 0)
                        <article class="bst-card bst-card--empty">
                            <div class="bst-card__avatar">—</div>
                            <div class="bst-card__body">
                                <span class="bst-card__role">نماینده</span>
                                <span class="bst-card__name">نماینده‌ای ثبت نشده</span>
                            </div>
                        </article>
                    @else
                        <ul class="bst__rep-grid">
                            @foreach ($reps as $rep)
                                <li>
                                    @include('filament.pages.partials.bankdar-subtree-card', [
                                        'node' => $rep,
                                        'roleLabel' => $roleLabels['representative'],
                                    ])
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </li>
        </ol>
    </div>

    <style>
        .bst {
            --bst-ink: #0f172a;
            --bst-muted: #64748b;
            --bst-line: #e2e8f0;
            --bst-surface: #ffffff;
            --bst-hero-from: #f8fafc;
            --bst-hero-to: #eef2ff;
            --bst-sm: #4f46e5;
            --bst-bankdar: #0f766e;
            --bst-rep: #15803d;
            max-width: 56rem;
        }
        .dark .bst {
            --bst-ink: #f8fafc;
            --bst-muted: #94a3b8;
            --bst-line: #334155;
            --bst-surface: #1e293b;
            --bst-hero-from: #0f172a;
            --bst-hero-to: #1e1b4b;
        }

        .bst__hero {
            display: flex;
            flex-wrap: wrap;
            gap: 1.25rem;
            align-items: flex-end;
            justify-content: space-between;
            margin-bottom: 1.75rem;
            padding: 1.35rem 1.4rem;
            border: 1px solid var(--bst-line);
            border-radius: 1rem;
            background: linear-gradient(135deg, var(--bst-hero-from), var(--bst-hero-to));
        }
        .bst__kicker {
            margin: 0 0 0.35rem;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: var(--bst-bankdar);
            text-transform: none;
        }
        .bst__title {
            margin: 0;
            font-size: 1.45rem;
            font-weight: 800;
            color: var(--bst-ink);
            line-height: 1.3;
        }
        .bst__intro {
            margin: 0.45rem 0 0;
            color: var(--bst-muted);
            font-size: 0.92rem;
            max-width: 28rem;
        }
        .bst__stats {
            display: flex;
            gap: 0.75rem;
        }
        .bst__stat {
            min-width: 7rem;
            padding: 0.75rem 1rem;
            border-radius: 0.85rem;
            background: color-mix(in srgb, var(--bst-surface) 88%, transparent);
            border: 1px solid var(--bst-line);
            text-align: center;
        }
        .bst__stat-value {
            display: block;
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--bst-ink);
            font-variant-numeric: tabular-nums;
        }
        .bst__stat-label {
            display: block;
            margin-top: 0.15rem;
            font-size: 0.75rem;
            color: var(--bst-muted);
        }

        .bst__tree {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0;
        }
        .bst__level {
            display: grid;
            grid-template-columns: 1.5rem 1fr;
            gap: 0.85rem;
            position: relative;
        }
        .bst__level:not(:last-child) .bst__rail::after {
            content: '';
            position: absolute;
            top: 1.65rem;
            bottom: -0.15rem;
            right: 0.65rem;
            width: 2px;
            background: linear-gradient(to bottom, var(--bst-line), transparent);
        }
        .bst__rail {
            position: relative;
            display: flex;
            justify-content: center;
            padding-top: 1.15rem;
        }
        .bst__dot {
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 999px;
            background: var(--bst-line);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--bst-surface) 80%, transparent);
            z-index: 1;
        }
        .bst__level--sm .bst__dot { background: var(--bst-sm); }
        .bst__level--bankdar .bst__dot { background: var(--bst-bankdar); }
        .bst__level--reps .bst__dot { background: var(--bst-rep); }
        .bst__content {
            padding-bottom: 1.15rem;
            min-width: 0;
        }

        .bst__section-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 0.82rem;
            font-weight: 700;
            color: var(--bst-muted);
        }
        .bst__section-count {
            font-weight: 600;
            color: var(--bst-rep);
            background: color-mix(in srgb, var(--bst-rep) 12%, transparent);
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
        }

        .bst__rep-grid {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 0.75rem;
            grid-template-columns: 1fr;
        }
        @media (min-width: 720px) {
            .bst__rep-grid { grid-template-columns: 1fr 1fr; }
        }

        .bst-card {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 0.85rem;
            align-items: start;
            padding: 0.95rem 1rem;
            border: 1px solid var(--bst-line);
            border-radius: 0.95rem;
            background: var(--bst-surface);
            box-shadow: 0 1px 0 color-mix(in srgb, var(--bst-ink) 4%, transparent);
            transition: border-color 0.15s ease, transform 0.15s ease;
        }
        .bst-card:hover {
            border-color: color-mix(in srgb, var(--bst-bankdar) 35%, var(--bst-line));
        }
        .bst-card--sales_manager {
            border-inline-start: 3px solid var(--bst-sm);
        }
        .bst-card--bankdar {
            border-inline-start: 3px solid var(--bst-bankdar);
        }
        .bst-card--representative {
            border-inline-start: 3px solid var(--bst-rep);
        }
        .bst-card--empty {
            opacity: 0.9;
            background: color-mix(in srgb, var(--bst-surface) 70%, #f8fafc);
        }
        .bst-card__avatar {
            width: 2.55rem;
            height: 2.55rem;
            border-radius: 0.85rem;
            display: grid;
            place-items: center;
            font-size: 0.85rem;
            font-weight: 800;
            color: #fff;
            background: #64748b;
            flex-shrink: 0;
        }
        .bst-card--sales_manager .bst-card__avatar { background: linear-gradient(145deg, #6366f1, #4338ca); }
        .bst-card--bankdar .bst-card__avatar { background: linear-gradient(145deg, #14b8a6, #0f766e); }
        .bst-card--representative .bst-card__avatar { background: linear-gradient(145deg, #4ade80, #15803d); }
        .bst-card--empty .bst-card__avatar { background: #94a3b8; }
        .bst-card__top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem 0.65rem;
            margin-bottom: 0.2rem;
        }
        .bst-card__role {
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            color: var(--bst-muted);
        }
        .bst-card--sales_manager .bst-card__role { color: var(--bst-sm); }
        .bst-card--bankdar .bst-card__role { color: var(--bst-bankdar); }
        .bst-card--representative .bst-card__role { color: var(--bst-rep); }
        .bst-card__badge {
            font-size: 0.7rem;
            font-weight: 700;
            color: var(--bst-rep);
            background: color-mix(in srgb, var(--bst-rep) 12%, transparent);
            padding: 0.12rem 0.45rem;
            border-radius: 999px;
        }
        .bst-card__name {
            display: inline-block;
            font-size: 1.02rem;
            font-weight: 800;
            color: var(--bst-ink);
            text-decoration: none;
            line-height: 1.35;
        }
        a.bst-card__name:hover { text-decoration: underline; }
        .bst-card__chips {
            list-style: none;
            margin: 0.55rem 0 0;
            padding: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
        }
        .bst-card__chips li {
            font-size: 0.75rem;
            color: var(--bst-muted);
            background: color-mix(in srgb, var(--bst-muted) 10%, transparent);
            padding: 0.2rem 0.5rem;
            border-radius: 0.45rem;
            font-variant-numeric: tabular-nums;
            direction: ltr;
            unicode-bidi: isolate;
        }
        .bst-card__hint {
            margin: 0.35rem 0 0;
            font-size: 0.82rem;
            color: var(--bst-muted);
        }
    </style>
</x-filament-panels::page>
