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

<div class="rounded-xl border border-gray-200/90 dark:border-white/10 bg-white dark:bg-gray-950 p-4 space-y-2 shadow-sm">
    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 flex items-center gap-2">
        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
        پیش‌نمایش در گوگل
    </div>
    <div class="rounded-lg border border-gray-100 dark:border-white/5 bg-[#f8f9fa] dark:bg-gray-900/80 p-3 text-right" dir="rtl">
        <div class="text-[#1a0dab] dark:text-blue-400 text-lg leading-snug line-clamp-2 break-words">
            {{ $titleForSerp !== '' ? $titleForSerp : '— عنوان را در بالا بنویسید —' }}
        </div>
        <div class="mt-0.5 text-xs text-[#006621] dark:text-emerald-500/90 truncate" dir="ltr">
            {{ $siteName }} › …
        </div>
        <div class="mt-1 text-sm text-[#4d5156] dark:text-gray-400 leading-relaxed line-clamp-3 break-words">
            {{ $description !== '' ? $description : '— توضیح متا را اضافه کنید —' }}
        </div>
    </div>
    <div class="flex flex-wrap gap-3 text-[11px] text-gray-500 dark:text-gray-400">
        <span>طول عنوان: <strong class="text-gray-800 dark:text-gray-200">{{ $titleLen }}</strong> کاراکتر</span>
        <span>طول توضیح: <strong class="text-gray-800 dark:text-gray-200">{{ $descLen }}</strong> کاراکتر</span>
    </div>
</div>
