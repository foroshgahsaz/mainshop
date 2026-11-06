@php
    use App\Support\AdminContentSeoAnalyzer;

    $checks = AdminContentSeoAnalyzer::analyze($state ?? []);
    $score = AdminContentSeoAnalyzer::overallScore($checks);
    $dot = fn (string $s) => match ($s) {
        'good' => '#16a34a',
        'ok' => '#ca8a04',
        'bad' => '#dc2626',
        default => '#6b7280',
    };
@endphp
<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/40 p-4 space-y-3 text-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="font-semibold text-gray-800 dark:text-gray-100">تحلیل SEO (سبک Yoast، بدون AI)</div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-500 dark:text-gray-400">امتیاز تقریبی</span>
            <span class="inline-flex items-center justify-center min-w-[2.75rem] rounded-md bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 px-2 py-0.5 font-bold text-gray-900 dark:text-white">{{ $score }}</span>
            <span class="text-xs text-gray-400">/۱۰۰</span>
        </div>
    </div>
    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">
        «کلمهٔ کلیدی تمرکز» را بنویسید؛ بررسی روی عنوان، متا، پاراگراف‌ها و سرفصل‌ها است. برای به‌روزرسانی نتیجه پس از تغییر متن طولانی، فیلد دیگری را لمس کنید یا کمی صبر کنید (debounce).
    </p>
    @if(count($checks) === 1 && ($checks[0]['id'] ?? '') === 'focus_keyword')
        <div class="text-amber-700 dark:text-amber-400 text-xs">{{ $checks[0]['detail'] }}</div>
    @else
        <ul class="space-y-2">
            @foreach($checks as $row)
                <li class="flex gap-2 items-start">
                    <span class="mt-1 inline-block h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $dot($row['status']) }};" title="{{ $row['status'] }}"></span>
                    <div>
                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $row['label'] }}</div>
                        <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5 leading-relaxed">{{ $row['detail'] }}</div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>
