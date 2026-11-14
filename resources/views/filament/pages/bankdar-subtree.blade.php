<x-filament-panels::page>
    @php
        $salesManager = $tree['sales_manager'] ?? null;
        $bankdarNode = $tree['bankdar'] ?? null;
        $reps = $tree['representatives'] ?? [];
        $roleLabels = [
            'sales_manager' => 'مدیر فروش',
            'bankdar' => 'بنکدار',
            'representative' => 'نماینده',
        ];
    @endphp

    <div class="bankdar-subtree" dir="rtl">
        <p class="bankdar-subtree__intro">
            سلسله‌مراتب از مدیر فروش تا نمایندگان این بنکدار.
        </p>

        <ol class="bankdar-subtree__tree">
            <li class="bankdar-subtree__node bankdar-subtree__node--sm">
                @if ($salesManager)
                    @include('filament.pages.partials.bankdar-subtree-card', [
                        'node' => $salesManager,
                        'roleLabel' => $roleLabels['sales_manager'],
                    ])
                @else
                    <div class="bankdar-subtree__card bankdar-subtree__card--empty">
                        <span class="bankdar-subtree__role">مدیر فروش</span>
                        <strong>تعیین نشده</strong>
                        <p class="bankdar-subtree__meta">برای این بنکدار مدیر فروش ثبت نشده است.</p>
                    </div>
                @endif

                <ol class="bankdar-subtree__branch">
                    <li class="bankdar-subtree__node bankdar-subtree__node--bankdar">
                        @if ($bankdarNode)
                            @include('filament.pages.partials.bankdar-subtree-card', [
                                'node' => $bankdarNode,
                                'roleLabel' => $roleLabels['bankdar'],
                            ])
                        @endif

                        <ol class="bankdar-subtree__branch">
                            @forelse ($reps as $rep)
                                <li class="bankdar-subtree__node bankdar-subtree__node--rep">
                                    @include('filament.pages.partials.bankdar-subtree-card', [
                                        'node' => $rep,
                                        'roleLabel' => $roleLabels['representative'],
                                    ])
                                </li>
                            @empty
                                <li class="bankdar-subtree__node">
                                    <div class="bankdar-subtree__card bankdar-subtree__card--empty">
                                        <span class="bankdar-subtree__role">نماینده</span>
                                        <strong>نماینده‌ای ثبت نشده</strong>
                                    </div>
                                </li>
                            @endforelse
                        </ol>
                    </li>
                </ol>
            </li>
        </ol>
    </div>

    <style>
        .bankdar-subtree { max-width: 52rem; }
        .bankdar-subtree__intro {
            margin: 0 0 1.25rem;
            color: #64748b;
            font-size: 0.95rem;
        }
        .bankdar-subtree__tree,
        .bankdar-subtree__branch {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .bankdar-subtree__branch {
            margin-top: 0.75rem;
            padding-right: 1.25rem;
            border-right: 2px solid #e2e8f0;
        }
        .bankdar-subtree__node + .bankdar-subtree__node {
            margin-top: 0.75rem;
        }
        .bankdar-subtree__card {
            display: grid;
            gap: 0.25rem;
            padding: 0.9rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            background: #fff;
        }
        .bankdar-subtree__card--empty { background: #f8fafc; color: #64748b; }
        .bankdar-subtree__node--sm > .bankdar-subtree__card { border-color: #c7d2fe; background: #eef2ff; }
        .bankdar-subtree__node--bankdar > .bankdar-subtree__card { border-color: #ddd6fe; background: #f5f3ff; }
        .bankdar-subtree__node--rep > .bankdar-subtree__card { border-color: #bbf7d0; background: #f0fdf4; }
        .bankdar-subtree__role {
            font-size: 0.75rem;
            font-weight: 700;
            color: #6366f1;
            letter-spacing: 0.02em;
        }
        .bankdar-subtree__node--bankdar .bankdar-subtree__role { color: #7c3aed; }
        .bankdar-subtree__node--rep .bankdar-subtree__role { color: #15803d; }
        .bankdar-subtree__name {
            font-size: 1.05rem;
            color: #0f172a;
            text-decoration: none;
        }
        .bankdar-subtree__name:hover { text-decoration: underline; }
        .bankdar-subtree__meta {
            margin: 0;
            font-size: 0.85rem;
            color: #64748b;
        }
    </style>
</x-filament-panels::page>
