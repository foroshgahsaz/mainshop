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

    $seoGood = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'good')) : 0;
    $seoOk = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'ok')) : 0;
    $seoBad = $hasFocus ? count(array_filter($seoChecks, fn ($c) => ($c['status'] ?? '') === 'bad')) : 0;

    $readGood = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'good'));
    $readOk = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'ok'));
    $readBad = count(array_filter($readChecks, fn ($c) => ($c['status'] ?? '') === 'bad'));

    $issueCount = $seoBad + $readBad;
@endphp

<div class="ss-stats">
    <div class="ss-stat">
        <div class="ss-stat-icon" style="background:#f0eaff;color:#7047f5">◈</div>
        <div>
            <strong>{{ $weighted }}</strong>
            <small>امتیاز ترکیبی</small>
        </div>
    </div>
    <div class="ss-stat">
        <div class="ss-stat-icon" style="background:#e9f8f1;color:#16ad7a">✓</div>
        <div>
            <strong>{{ $hasFocus ? $seoGood + $readGood : $readGood }}</strong>
            <small>بررسی موفق</small>
        </div>
    </div>
    <div class="ss-stat">
        <div class="ss-stat-icon" style="background:#fff3e5;color:#e9a13c">⚑</div>
        <div>
            <strong>{{ $issueCount }}</strong>
            <small>نیازمند اصلاح</small>
        </div>
    </div>
</div>
