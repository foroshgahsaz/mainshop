@php
    use App\Services\Settings\SettingsService;

    $primaryKey = $primary_title_key ?? 'title';

    $evaluated = $evaluate(function (\Filament\Forms\Get $get) use ($primaryKey): array {
        $metaTitle = trim((string) $get('meta_title'));
        $primaryTitle = trim((string) $get($primaryKey));
        $titleForSerp = $metaTitle !== '' ? $metaTitle : $primaryTitle;

        return [
            'title_for_serp' => $titleForSerp,
            'description' => (string) $get('meta_description'),
        ];
    });

    $titleForSerp = $evaluated['title_for_serp'] ?? '';
    $description = $evaluated['description'] ?? '';

    $site = app(SettingsService::class)->site();
    $siteName = (string) ($site['name'] ?? config('app.name', 'فروشگاه'));

    $titleLen = mb_strlen($titleForSerp);
    $descLen = mb_strlen($description);
@endphp

<div class="ss-google-preview" dir="rtl">
    <div class="ss-preview-head">◉ پیش‌نمایش نتیجهٔ جستجوی گوگل</div>
    <div class="ss-google-url" dir="ltr">{{ $siteName }} › …</div>
    <div class="ss-google-title">
        {{ $titleForSerp !== '' ? $titleForSerp : 'عنوان صفحه در نتایج گوگل نمایش داده می‌شود' }}
    </div>
    <div class="ss-google-desc">
        {{ $description !== '' ? $description : 'توضیحات متای صفحه را اضافه کنید تا در نتایج دیده شود.' }}
    </div>
    <div class="ss-preview-meta">
        <span>طول عنوان: <strong>{{ $titleLen }}</strong> کاراکتر</span>
        <span>طول توضیح: <strong>{{ $descLen }}</strong> کاراکتر</span>
    </div>
</div>
