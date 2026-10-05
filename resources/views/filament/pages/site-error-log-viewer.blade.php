<x-filament-panels::page>
    <div class="admin-payment-log-viewer" data-site-error-logs data-ui-version="{{ \App\Filament\Pages\ViewSiteErrorLogs::UI_VERSION }}">
        <section class="admin-order__section">
            <h2 class="admin-order__section-title">جستجو در لاگ (حداکثر ۳ فایل اخیر)</h2>
            <form method="get" class="admin-payment-log-viewer__form" action="{{ \App\Filament\Pages\ViewSiteErrorLogs::getUrl() }}">
                <div class="admin-payment-log-viewer__fields">
                    <label class="admin-payment-log-viewer__field">
                        <span>متن (خطا، URL، کد رهگیری و …)</span>
                        <input
                            type="search"
                            name="q"
                            value="{{ $searchQuery }}"
                            class="admin-payment-log-viewer__input"
                            placeholder="مثلاً production.ERROR یا Payment verify"
                            dir="ltr"
                        >
                    </label>
                    <div class="admin-payment-log-viewer__actions">
                        <x-filament::button type="submit" size="sm">جستجو</x-filament::button>
                        @if ($searchQuery !== '')
                            <x-filament::button tag="a" href="{{ \App\Filament\Pages\ViewSiteErrorLogs::getUrl() }}" size="sm" color="gray">
                                پاک کردن
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </form>

            @if ($searchQuery !== '')
                <p class="admin-order__hint">
                    نتیجه برای «<strong dir="ltr">{{ $searchQuery }}</strong>»
                    @if ($searchScannedFiles !== [])
                        — فایل‌ها:
                        <code dir="ltr">{{ implode(', ', $searchScannedFiles) }}</code>
                    @endif
                </p>
                @if ($searchTruncated)
                    <p class="admin-payment-log-viewer__warn">نمایش محدود به ۲۰۰ خط اول؛ برای جزئیات بیشتر فایل را دانلود کنید.</p>
                @endif
                @if ($searchLines === [])
                    <p class="admin-payment-log-viewer__empty">موردی پیدا نشد.</p>
                @else
                    <pre class="admin-payment-log-viewer__pre" dir="ltr">@foreach ($searchLines as $line){{ $line }}
@endforeach</pre>
                @endif
            @endif
        </section>

        <section class="admin-order__section">
            <h2 class="admin-order__section-title">پیش‌نمایش خطاهای امروز</h2>
            <p class="admin-order__hint">
                فقط انتهای فایل امروز (حداکثر ۲۵۶ کیلوبایت) خوانده می‌شود — بدون بار اضافی روی سرور.
                @if ($errorPreviewSource)
                    منبع: <code dir="ltr">{{ $errorPreviewSource }}</code>
                @endif
            </p>
            @if ($errorPreviewTruncated)
                <p class="admin-payment-log-viewer__warn">خطوط بیشتر وجود دارد؛ فایل کامل را دانلود کنید.</p>
            @endif
            @if ($errorPreviewLines === [])
                <p class="admin-payment-log-viewer__empty">خطای ERROR/CRITICAL در انتهای لاگ امروز نیست (یا فایل لاگ وجود ندارد).</p>
            @else
                <pre class="admin-payment-log-viewer__pre" dir="ltr">@foreach ($errorPreviewLines as $line){{ $line }}
@endforeach</pre>
            @endif
        </section>

        <section class="admin-order__section">
            <h2 class="admin-order__section-title">۲۰ روز اخیر — دانلود</h2>
            <p class="admin-order__hint">
                مسیر: <code dir="ltr">{{ $logsDirectory }}</code>
                — فقط <code dir="ltr">laravel-YYYY-MM-DD.log</code> و <code dir="ltr">laravel.log</code>
                (لاگ پرداخت جداست).
            </p>

            @if ($legacyLogEntry)
                <p class="admin-order__hint admin-payment-log-viewer__legacy">
                    فایل تجمیعی:
                    <x-filament::button tag="a" href="{{ $legacyLogEntry['download_url'] }}" size="sm" color="gray">
                        دانلود {{ $legacyLogEntry['basename'] }}
                    </x-filament::button>
                    <span dir="ltr">({{ $this->formatSize($legacyLogEntry['size_bytes']) }})</span>
                </p>
            @endif

            <div class="admin-payment-log-viewer__table-wrap">
                <table class="admin-payment-log-viewer__table">
                    <thead>
                        <tr>
                            <th>تاریخ</th>
                            <th>نام فایل</th>
                            <th>وضعیت</th>
                            <th>حجم</th>
                            <th>دانلود</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentEntries as $entry)
                            <tr>
                                <td>{{ $entry['label'] }}</td>
                                <td dir="ltr" class="admin-payment-log-viewer__filename">
                                    {{ $entry['basename'] ?? '—' }}
                                </td>
                                <td>
                                    @if ($entry['kind'] === 'log')
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--ok">موجود</span>
                                    @else
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--empty">بدون فایل</span>
                                    @endif
                                </td>
                                <td dir="ltr">{{ $this->formatSize($entry['size_bytes']) }}</td>
                                <td>
                                    @if ($entry['download_url'])
                                        <x-filament::button tag="a" href="{{ $entry['download_url'] }}" size="sm">
                                            دانلود
                                        </x-filament::button>
                                    @else
                                        <span class="admin-payment-log-viewer__muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
