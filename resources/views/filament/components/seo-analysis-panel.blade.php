@php
    use App\Support\AdminContentSeoAnalyzer;
    use Filament\Forms\Get;

    $primaryKey = $primary_title_key ?? 'title';
    $contentKey = $content_key ?? 'content';
    $shortKey = $short_key ?? null;

    $state = $evaluate(function (Get $get) use ($primaryKey, $contentKey, $shortKey): array {
        $short = $shortKey !== null && $shortKey !== '' ? (string) $get($shortKey) : '';

        return [
            'focus_keyword' => (string) $get('focus_keyword'),
            'primary_title' => (string) $get($primaryKey),
            'meta_title' => (string) $get('meta_title'),
            'meta_description' => (string) $get('meta_description'),
            'content_html' => (string) $get($contentKey),
            'short_text' => $short,
        ];
    });

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

    $bulb = function (string $status): string {
        return match ($status) {
            'good' => 'bg-emerald-500 shadow-[0_0_0_3px_rgba(16,185,129,0.35)]',
            'ok' => 'bg-amber-400 shadow-[0_0_0_3px_rgba(251,191,36,0.35)]',
            'bad' => 'bg-red-500 shadow-[0_0_0_3px_rgba(239,68,68,0.35)]',
            default => 'bg-gray-400 shadow-[0_0_0_3px_rgba(156,163,175,0.3)]',
        };
    };

    $statusUi = function (string $status): array {
        return match ($status) {
            'good' => [
                'badge' => 'bg-emerald-500/12 text-emerald-900 dark:text-emerald-100 border-emerald-500/30',
                'label' => 'سبز',
            ],
            'ok' => [
                'badge' => 'bg-amber-500/12 text-amber-950 dark:text-amber-100 border-amber-500/30',
                'label' => 'زرد',
            ],
            'bad' => [
                'badge' => 'bg-red-500/12 text-red-900 dark:text-red-100 border-red-500/30',
                'label' => 'قرمز',
            ],
            default => [
                'badge' => 'bg-gray-500/10 text-gray-700 dark:text-gray-200 border-gray-500/20',
                'label' => '—',
            ],
        };
    };

    $scoreStripClass = function (int $s): string {
        if ($s >= 78) {
            return 'bg-emerald-600';
        }
        if ($s >= 52) {
            return 'bg-amber-500';
        }

        return 'bg-red-600';
    };
@endphp

<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-[#f7f7f7] dark:bg-gray-900 overflow-hidden shadow-sm">
    {{-- Yoast-like score header --}}
    <div class="flex items-stretch border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-center px-4 py-3 min-w-[5.5rem] {{ $scoreStripClass($seoScore) }} text-white font-black text-xl tabular-nums">
            {{ $seoScore }}
        </div>
        <div class="flex-1 px-4 py-3 bg-white dark:bg-gray-950 flex flex-wrap items-center gap-x-4 gap-y-2">
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100">تحلیل سئو</span>
            <span class="text-xs text-gray-500 dark:text-gray-400 hidden sm:inline">بدون AI — مشابه Yoast SEO</span>
            @if($hasFocus)
                <div class="flex items-center gap-2 ms-auto" title="خلاصهٔ چراغ‌ها">
                    <span class="flex h-3.5 w-3.5 rounded-full {{ $bulb('good') }}" aria-hidden="true"></span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $seoGood }}</span>
                    <span class="flex h-3.5 w-3.5 rounded-full {{ $bulb('ok') }}" aria-hidden="true"></span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $seoOk }}</span>
                    <span class="flex h-3.5 w-3.5 rounded-full {{ $bulb('bad') }}" aria-hidden="true"></span>
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $seoBad }}</span>
                </div>
            @endif
        </div>
        <div class="hidden sm:flex items-center justify-center px-4 py-3 min-w-[5.5rem] bg-sky-600 text-white font-black text-xl tabular-nums border-s border-gray-200 dark:border-gray-700">
            {{ $readScore }}
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-0 lg:divide-x lg:divide-gray-200 dark:lg:divide-gray-700 bg-white dark:bg-gray-950">
        {{-- SEO column --}}
        <div class="p-4 space-y-3">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                    سئو
                </h3>
            </div>

            @if(!$hasFocus && count($seoChecks) === 1 && ($seoChecks[0]['id'] ?? '') === 'focus_keyword')
                <div class="rounded-md border border-amber-200 bg-amber-50 dark:bg-amber-950/50 dark:border-amber-800/50 p-3 text-xs text-amber-950 dark:text-amber-100 leading-relaxed">
                    {{ $seoChecks[0]['detail'] }}
                </div>
            @else
                <ul class="space-y-2">
                    @foreach($seoChecks as $row)
                        @php $u = $statusUi($row['status'] ?? ''); @endphp
                        <li class="flex gap-3 items-start rounded border border-gray-100 dark:border-gray-800 bg-[#fafafa] dark:bg-gray-900/50 p-2.5">
                            <span class="mt-1 flex h-3.5 w-3.5 shrink-0 rounded-full {{ $bulb($row['status'] ?? '') }}" title="{{ $u['label'] }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row['label'] }}</span>
                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded border {{ $u['badge'] }}">{{ $u['label'] }}</span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">{{ $row['detail'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Readability column --}}
        <div class="p-4 space-y-3 border-t lg:border-t-0 border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                    خوانایی
                </h3>
                <div class="sm:hidden flex items-center gap-1 text-xs font-bold text-sky-700 dark:text-sky-300">
                    امتیاز: {{ $readScore }}
                </div>
                <div class="flex items-center gap-2 text-[10px] text-gray-500 dark:text-gray-400">
                    <span class="flex h-3 w-3 rounded-full {{ $bulb('good') }}"></span><span>{{ $readGood }}</span>
                    <span class="flex h-3 w-3 rounded-full {{ $bulb('ok') }}"></span><span>{{ $readOk }}</span>
                    <span class="flex h-3 w-3 rounded-full {{ $bulb('bad') }}"></span><span>{{ $readBad }}</span>
                </div>
            </div>
            <ul class="space-y-2">
                @foreach($readChecks as $row)
                    @php $u = $statusUi($row['status'] ?? ''); @endphp
                    <li class="flex gap-3 items-start rounded border border-gray-100 dark:border-gray-800 bg-[#fafafa] dark:bg-gray-900/50 p-2.5">
                        <span class="mt-1 flex h-3.5 w-3.5 shrink-0 rounded-full {{ $bulb($row['status'] ?? '') }}"></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $row['label'] }}</span>
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded border {{ $u['badge'] }}">{{ $u['label'] }}</span>
                            </div>
                            <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 leading-relaxed">{{ $row['detail'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
