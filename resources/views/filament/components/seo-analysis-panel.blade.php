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

    $weighted = (int) round($seoScore * 0.55 + $readScore * 0.45);
    $weighted = max(0, min(100, $weighted));

    $circumference = 2 * M_PI * 65;
    $dashOffset = $circumference - ($circumference * $weighted / 100);

    $seoGood = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'good')) : 0;
    $seoOk = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'ok')) : 0;
    $seoBad = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'bad')) : 0;

    $readGood = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'good'));
    $readOk = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'ok'));
    $readBad = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'bad'));

    $totalGood = $hasFocus ? ($seoGood + $readGood) : $readGood;
    $totalOk = $hasFocus ? ($seoOk + $readOk) : $readOk;
    $totalBad = $hasFocus ? ($seoBad + $readBad) : $readBad;

    $checkIcon = function (string $status): array {
        return match ($status) {
            'good' => ['cls' => 'ok', 'sym' => '✓'],
            'ok' => ['cls' => 'warn', 'sym' => '!'],
            'bad' => ['cls' => 'fail', 'sym' => '×'],
            default => ['cls' => 'warn', 'sym' => '•'],
        };
    };

    $scoreLabelClass = $weighted >= 78 ? 'ss-score-note--good' : ($weighted >= 52 ? 'ss-score-note--mid' : 'ss-score-note--bad');
    $scoreLabelText = $weighted >= 78 ? '✦ عملکرد خوب است' : ($weighted >= 52 ? '◉ نیازمند بهبود' : '⚑ نیازمند اصلاح جدی');

    $checklist = array_slice(array_merge($seoChecks, $readChecks), 0, 6);
@endphp

<aside class="ss-side" wire:ignore.self>
    <div class="ss-card score-card">
        <div class="ss-card-pad ss-gauge-wrap">
            <h4>امتیاز کلی بهینه‌سازی</h4>
            <div class="ss-gauge">
                <svg viewBox="0 0 150 150" aria-hidden="true">
                    <defs>
                        <linearGradient id="ss-grad-seo" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#7047f5"/>
                            <stop offset="100%" stop-color="#b08aff"/>
                        </linearGradient>
                    </defs>
                    <circle class="track" cx="75" cy="75" r="65"/>
                    <circle class="value" cx="75" cy="75" r="65" style="stroke-dashoffset: {{ $dashOffset }}"/>
                </svg>
                <div class="ss-gauge-center">
                    <div>
                        <strong>{{ $weighted }}</strong>
                        <small>از ۱۰۰</small>
                    </div>
                </div>
            </div>
            <div class="ss-score-note {{ $scoreLabelClass }}">{{ $scoreLabelText }}</div>
            <div class="ss-score-desc">
                امتیاز ترکیبی از تحلیل سئو ({{ $seoScore }}) و خوانایی ({{ $readScore }}). با اصلاح موارد قرمز و زرد نتیجه بهتر می‌شود.
            </div>
            <div class="ss-progress-row">
                <div class="ss-progress-label"><span>سئوی محتوا</span><b>{{ $seoScore }}٪</b></div>
                <div class="ss-progress"><i style="width: {{ $seoScore }}%"></i></div>
            </div>
            <div class="ss-progress-row">
                <div class="ss-progress-label"><span>خوانایی</span><b>{{ $readScore }}٪</b></div>
                <div class="ss-progress"><i style="width: {{ $readScore }}%"></i></div>
            </div>
        </div>
    </div>

    <div class="ss-card">
        <div class="ss-card-pad">
            <h3 class="ss-check-title">چک‌لیست</h3>
            @forelse($checklist as $row)
                @php $ic = $checkIcon($row['status'] ?? ''); @endphp
                <div class="ss-check">
                    <div class="ss-check-icon {{ $ic['cls'] }}">{{ $ic['sym'] }}</div>
                    <div>
                        <strong>{{ $row['label'] ?? '' }}</strong>
                        <small>{{ $row['detail'] ?? '' }}</small>
                    </div>
                </div>
            @empty
                <div class="ss-check">
                    <div class="ss-check-icon ok">✓</div>
                    <div><strong>بدون داده</strong><small>فیلدها را تکمیل کنید.</small></div>
                </div>
            @endforelse
        </div>
    </div>

    <div class="ss-card">
        <div class="ss-card-pad ss-issues">
            <h3 class="ss-check-title">گزارش وضعیت</h3>
            <div class="ss-issue">
                <span>موارد موفق</span>
                <b class="ss-green">{{ $totalGood }} مورد</b>
            </div>
            <div class="ss-issue">
                <span>هشدارها</span>
                <b class="ss-orange">{{ $totalOk }} مورد</b>
            </div>
            <div class="ss-issue">
                <span>خطاها</span>
                <b class="ss-red">{{ $totalBad }} مورد</b>
            </div>
            <div class="ss-issue">
                <span>وضعیت کلی</span>
                <b class="{{ $weighted >= 65 ? 'ss-green' : ($weighted >= 40 ? 'ss-orange' : 'ss-red') }}">
                    {{ $weighted >= 78 ? 'مطلوب' : ($weighted >= 52 ? 'متوسط' : 'ضعیف') }}
                </b>
            </div>
        </div>
    </div>
</aside>
