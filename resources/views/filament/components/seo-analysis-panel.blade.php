@php
    use App\Support\AdminContentSeoAnalyzer;

    $analysis = AdminContentSeoAnalyzer::analyzeFull($state ?? []);
    $seoChecks = $analysis['seo'] ?? [];
    $readChecks = $analysis['readability'] ?? [];
    $hasFocus = (bool) ($analysis['has_focus'] ?? false);
    $seoScore = AdminContentSeoAnalyzer::overallScore($seoChecks);
    $readScore = AdminContentSeoAnalyzer::overallScore($readChecks);

    $seoGood = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'good')) : 0;
    $seoOk = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'ok')) : 0;
    $seoBad = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'bad')) : 0;

    $readGood = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'good'));
    $readOk = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'ok'));
    $readBad = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'bad'));

    $statusUi = function (string $status): array {
        return match ($status) {
            'good' => [
                'bar' => 'bg-emerald-500',
                'ring' => 'ring-emerald-500/30',
                'badge' => 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-200 border-emerald-500/25',
                'label' => 'عالی',
            ],
            'ok' => [
                'bar' => 'bg-amber-500',
                'ring' => 'ring-amber-500/30',
                'badge' => 'bg-amber-500/10 text-amber-900 dark:text-amber-100 border-amber-500/25',
                'label' => 'قابل بهبود',
            ],
            'bad' => [
                'bar' => 'bg-red-500',
                'ring' => 'ring-red-500/30',
                'badge' => 'bg-red-500/10 text-red-800 dark:text-red-200 border-red-500/25',
                'label' => 'نیاز به اصلاح',
            ],
            default => [
                'bar' => 'bg-gray-400',
                'ring' => 'ring-gray-400/30',
                'badge' => 'bg-gray-500/10 text-gray-700 dark:text-gray-200 border-gray-500/20',
                'label' => '—',
            ],
        };
    };

    $scoreColor = function (int $s): string {
        if ($s >= 78) {
            return 'from-emerald-600 to-emerald-500';
        }
        if ($s >= 52) {
            return 'from-amber-600 to-amber-500';
        }

        return 'from-red-600 to-red-500';
    };
@endphp

<div class="fi-section-content-ctn rounded-xl border border-gray-200/90 dark:border-white/10 bg-gradient-to-br from-slate-50 via-white to-violet-50/40 dark:from-gray-950 dark:via-gray-900 dark:to-violet-950/30 shadow-sm overflow-hidden">
    <div class="relative px-4 py-4 border-b border-gray-200/70 dark:border-white/10 bg-white/70 dark:bg-gray-900/60 backdrop-blur-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-violet-500/10 dark:bg-violet-400/15 px-3 py-1 text-xs font-semibold text-violet-800 dark:text-violet-200 ring-1 ring-violet-500/20">
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-90" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    تحلیل محتوا — سبک Yoast (بدون AI)
                </div>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 leading-relaxed max-w-xl">
                    «کلمهٔ کلیدی تمرکز» ستون چپ را فعال می‌کند؛ ستون راست «خوانایی» مستقل از کلید است. به‌روزرسانی با تأخیر کوتاه (debounce) بعد از تایپ.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <div class="flex items-center gap-2 rounded-lg border border-gray-200/80 dark:border-white/10 bg-white/90 dark:bg-gray-900/80 px-3 py-2 shadow-sm min-w-[7.5rem]">
                    <div class="h-10 w-10 shrink-0 rounded-lg bg-gradient-to-br {{ $scoreColor($seoScore) }} text-white flex items-center justify-center text-sm font-black shadow-inner ring-2 ring-white/30 dark:ring-black/20">
                        {{ $seoScore }}
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">SEO</div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">امتیاز</div>
                    </div>
                </div>
                <div class="flex items-center gap-2 rounded-lg border border-gray-200/80 dark:border-white/10 bg-white/90 dark:bg-gray-900/80 px-3 py-2 shadow-sm min-w-[7.5rem]">
                    <div class="h-10 w-10 shrink-0 rounded-lg bg-gradient-to-br from-sky-600 to-cyan-500 text-white flex items-center justify-center text-sm font-black shadow-inner ring-2 ring-white/30 dark:ring-black/20">
                        {{ $readScore }}
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] uppercase tracking-wide text-gray-400 font-semibold">خوانایی</div>
                        <div class="text-xs text-gray-600 dark:text-gray-300">امتیاز</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 lg:divide-x lg:divide-gray-200/80 dark:lg:divide-white/10">
        {{-- SEO column --}}
        <div class="p-4 space-y-3">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-violet-500 shadow-[0_0_0_3px_rgba(139,92,246,0.25)]"></span>
                    سئو و کلمهٔ کلیدی
                </h3>
                @if($hasFocus)
                    <div class="flex items-center gap-1.5 text-[10px] text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-emerald-800 dark:text-emerald-200">{{ $seoGood }} سبز</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 text-amber-900 dark:text-amber-100">{{ $seoOk }} زرد</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-red-800 dark:text-red-200">{{ $seoBad }} قرمز</span>
                    </div>
                @endif
            </div>

            @if(!$hasFocus && count($seoChecks) === 1 && ($seoChecks[0]['id'] ?? '') === 'focus_keyword')
                <div class="rounded-lg border border-amber-300/60 bg-amber-50/90 dark:bg-amber-950/40 dark:border-amber-500/30 p-3 text-xs text-amber-950 dark:text-amber-100 leading-relaxed">
                    {{ $seoChecks[0]['detail'] }}
                </div>
            @else
                <ul class="space-y-2">
                    @foreach($seoChecks as $row)
                        @php $u = $statusUi($row['status'] ?? ''); @endphp
                        <li class="group rounded-lg border border-gray-100 dark:border-white/5 bg-white/80 dark:bg-gray-900/40 p-2.5 flex gap-3 hover:border-violet-300/50 dark:hover:border-violet-500/25 transition-colors">
                            <span class="mt-0.5 flex h-6 w-1 shrink-0 rounded-full {{ $u['bar'] }} ring-2 {{ $u['ring'] }}" title="{{ $row['status'] ?? '' }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row['label'] }}</span>
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md border {{ $u['badge'] }}">{{ $u['label'] }}</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">{{ $row['detail'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Readability column --}}
        <div class="p-4 space-y-3 border-t lg:border-t-0 border-gray-200/80 dark:border-white/10">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full bg-cyan-500 shadow-[0_0_0_3px_rgba(6,182,212,0.25)]"></span>
                    خوانایی
                </h3>
                <div class="flex items-center gap-1.5 text-[10px] text-gray-500 dark:text-gray-400">
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-emerald-800 dark:text-emerald-200">{{ $readGood }} سبز</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-2 py-0.5 text-amber-900 dark:text-amber-100">{{ $readOk }} زرد</span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-red-500/10 px-2 py-0.5 text-red-800 dark:text-red-200">{{ $readBad }} قرمز</span>
                </div>
            </div>
            <ul class="space-y-2">
                @foreach($readChecks as $row)
                    @php $u = $statusUi($row['status'] ?? ''); @endphp
                    <li class="group rounded-lg border border-gray-100 dark:border-white/5 bg-white/80 dark:bg-gray-900/40 p-2.5 flex gap-3 hover:border-cyan-300/50 dark:hover:border-cyan-500/25 transition-colors">
                        <span class="mt-0.5 flex h-6 w-1 shrink-0 rounded-full {{ $u['bar'] }} ring-2 {{ $u['ring'] }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row['label'] }}</span>
                                <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md border {{ $u['badge'] }}">{{ $u['label'] }}</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">{{ $row['detail'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
