<x-filament-panels::page>
    <div class="admin-payment-log-viewer" data-admin-payment-log data-ui-version="{{ \App\Filament\Pages\ViewPaymentLogs::UI_VERSION }}">
        <section class="admin-order__section admin-payment-log-timeline">
            <h2 class="admin-order__section-title">مسیر پرداخت (از فایل لاگ)</h2>
            <p class="admin-order__hint">
                کد رهگیری پرداخت (مثل <code dir="ltr">PAY-…</code>) یا شناسه عددی پرداخت را وارد کنید.
                در هر مرحله‌ای که پاسخ درگاه ثبت شده باشد، دکمه <strong>پاسخ درگاه</strong> خام همان پاسخ را نشان می‌دهد.
            </p>
            <form method="get" class="admin-payment-log-viewer__form" action="{{ \App\Filament\Pages\ViewPaymentLogs::getUrl() }}">
                <div class="admin-payment-log-viewer__fields">
                    <label class="admin-payment-log-viewer__field">
                        <span>جستجو</span>
                        <input
                            type="search"
                            name="tracking"
                            value="{{ $searchQuery }}"
                            class="admin-payment-log-viewer__input"
                            placeholder="کد رهگیری یا payment_id"
                            dir="ltr"
                            autocomplete="off"
                        />
                    </label>
                    <div class="admin-payment-log-viewer__actions">
                        <x-filament::button type="submit" size="sm">نمایش تایم‌لاین</x-filament::button>
                        @if ($searchQuery !== '')
                            <x-filament::button tag="a" href="{{ \App\Filament\Pages\ViewPaymentLogs::getUrl() }}" size="sm" color="gray">
                                پاک کردن
                            </x-filament::button>
                        @endif
                    </div>
                </div>
            </form>

            @if ($searchQuery !== '')
                @if ($searchTruncated)
                    <p class="admin-payment-log-viewer__warn">نتیجه به {{ count($timelineEvents) }} رویداد محدود شد؛ برای جزئیات کامل فایل لاگ را دانلود کنید.</p>
                @endif
                @if ($searchScannedFiles !== [])
                    <p class="admin-order__hint admin-payment-log-timeline__meta">
                        @if ($searchReferenceKind === 'payment_id')
                            جستجو با شناسه پرداخت <code dir="ltr">{{ $searchQuery }}</code>
                        @else
                            جستجو با کد رهگیری <code dir="ltr">{{ $searchQuery }}</code>
                        @endif
                        — {{ count($searchScannedFiles) }} فایل اسکن شد
                        @if ($searchPrimaryFile)
                            (اولین: <code dir="ltr">{{ basename($searchPrimaryFile) }}</code>)
                        @endif
                    </p>
                @endif

                @if ($timelineEvents === [])
                    <p class="admin-payment-log-viewer__empty">رویدادی در لاگ پیدا نشد (۱۴ روز اخیر).</p>
                @else
                    <ol class="admin-payment-log-timeline__list">
                        @foreach ($timelineEvents as $index => $event)
                            @php
                                $tone = $this->timelineTone($event);
                                $payload = $event['gateway_payload'] ?? '';
                                $showButton = ($event['show_gateway_button'] ?? false) && $payload !== '';
                            @endphp
                            <li
                                class="admin-payment-log-timeline__item admin-payment-log-timeline__item--{{ $tone }}"
                                wire:key="payment-log-event-{{ $index }}-{{ $event['code'] ?? '' }}"
                            >
                                <div class="admin-payment-log-timeline__rail">
                                    <span class="admin-payment-log-timeline__dot" aria-hidden="true"></span>
                                </div>
                                <div class="admin-payment-log-timeline__card">
                                    <div class="admin-payment-log-timeline__head">
                                        <span class="admin-payment-log-timeline__progress">{{ (int) ($event['progress'] ?? 0) }}٪</span>
                                        <code dir="ltr" class="admin-payment-log-timeline__code">{{ $event['code'] ?? '—' }}</code>
                                        <time dir="ltr" class="admin-payment-log-timeline__time">{{ $event['timestamp'] ?? '' }}</time>
                                    </div>
                                    <p class="admin-payment-log-timeline__message">{{ $event['message'] ?? '' }}</p>
                                    <div class="admin-payment-log-timeline__meta-row">
                                        @if (! empty($event['gateway']))
                                            <span>درگاه: <code dir="ltr">{{ $event['gateway'] }}</code></span>
                                        @endif
                                        @if (! empty($event['status']))
                                            <span>وضعیت: <code dir="ltr">{{ $event['status'] }}</code></span>
                                        @endif
                                        @if (! empty($event['tracking_code']))
                                            <span>رهگیری: <code dir="ltr">{{ $event['tracking_code'] }}</code></span>
                                        @endif
                                    </div>
                                    @if ($showButton)
                                        <div class="admin-payment-log-timeline__gateway" x-data="{ open: false }">
                                            <button
                                                type="button"
                                                class="admin-payment-log-timeline__gateway-btn"
                                                @click="open = !open"
                                                :aria-expanded="open.toString()"
                                            >
                                                پاسخ درگاه
                                            </button>
                                            <pre
                                                class="admin-payment-log-timeline__gateway-pre"
                                                x-show="open"
                                                x-cloak
                                                dir="ltr"
                                            >{{ $payload }}</pre>
                                        </div>
                                    @elseif ($event['show_gateway_button'] ?? false)
                                        <p class="admin-payment-log-timeline__gateway-empty">پاسخ خام درگاه در این مرحله در لاگ ذخیره نشده (لاگ‌های قدیمی‌تر).</p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            @endif
        </section>

        <section class="admin-order__section">
            <h2 class="admin-order__section-title">لاگ پرداخت (روزانه)</h2>
            <p class="admin-order__hint">
                مسیر ذخیره: <code dir="ltr">{{ $logsDirectory }}</code>
                — فایل روزانه: <code dir="ltr">payments-YYYY-MM-DD.log</code>
                <br>
                {{ $persistenceSummary }}
            </p>

            @if ($directoryDiagnostics !== [])
                <div class="admin-payment-log-viewer__table-wrap">
                    <table class="admin-payment-log-viewer__table">
                        <thead>
                            <tr>
                                <th>مسیر جستجو</th>
                                <th>وجود</th>
                                <th>نوشتن</th>
                                <th>تعداد فایل لاگ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($directoryDiagnostics as $row)
                                <tr>
                                    <td dir="ltr">
                                        {{ $row['path'] }}
                                        @if ($row['is_primary'])
                                            <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--ok">فعال</span>
                                        @endif
                                    </td>
                                    <td>{{ $row['exists'] ? 'بله' : 'خیر' }}</td>
                                    <td>{{ $row['writable'] ? 'بله' : 'خیر' }}</td>
                                    <td dir="ltr">{{ $row['log_file_count'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @php
                    $paymentLogFiles = collect($directoryDiagnostics)->sum('log_file_count');
                @endphp
                @if ($paymentLogFiles === 0)
                    <p class="admin-payment-log-viewer__warn">
                        هیچ فایل <code dir="ltr">payments*.log</code> در مسیرهای بالا نیست.
                        روی Runflare: <code dir="ltr">FILESYSTEM_PUBLIC_ROOT=/data</code> و <code dir="ltr">SHOP_LOGS_DIRECTORY=/data/logs</code>،
                        یک دیسک مشترک روی <code dir="ltr">/data</code> (نه mount جدا برای <code dir="ltr">/storage/logs</code>)،
                        سپس <code dir="ltr">php artisan config:clear</code> و <code dir="ltr">php artisan shop:fix-storage-permissions</code>.
                    </p>
                @endif
            @endif

            @if ($legacyLogEntry)
                <p class="admin-order__hint admin-payment-log-viewer__legacy">
                    فایل قدیمی یک‌تکه:
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
                            @php $downloadUrl = $entry['download_url'] ?? null; @endphp
                            <tr @class(['admin-payment-log-viewer__row--clickable' => $downloadUrl]) @if($downloadUrl) onclick="window.location.href='{{ $downloadUrl }}'" @endif>
                                <td>{{ $entry['label'] }}</td>
                                <td dir="ltr" class="admin-payment-log-viewer__filename">
                                    @if ($downloadUrl)
                                        <a href="{{ $downloadUrl }}" class="admin-payment-log-viewer__file-link" onclick="event.stopPropagation()">{{ $entry['basename'] ?? '—' }}</a>
                                    @else
                                        {{ $entry['basename'] ?? '—' }}
                                    @endif
                                </td>
                                <td>
                                    @if ($entry['kind'] === 'log')
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--ok">موجود</span>
                                    @elseif ($entry['kind'] === 'zip')
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--zip">فشرده</span>
                                    @else
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--empty">بدون فایل</span>
                                    @endif
                                </td>
                                <td dir="ltr">{{ $this->formatSize($entry['size_bytes']) }}</td>
                                <td>
                                    @if ($downloadUrl)
                                        <x-filament::button tag="a" href="{{ $downloadUrl }}" size="sm" class="admin-payment-log-viewer__download-btn">
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

        @if ($archiveEntries !== [])
            <section class="admin-order__section">
                <h2 class="admin-order__section-title">آرشیو (بیش از ۲۰ روز)</h2>
                <div class="admin-payment-log-viewer__table-wrap">
                    <table class="admin-payment-log-viewer__table">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>نام فایل</th>
                                <th>حجم</th>
                                <th>دانلود</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($archiveEntries as $entry)
                                @php $downloadUrl = $entry['download_url'] ?? null; @endphp
                                <tr @class(['admin-payment-log-viewer__row--clickable' => $downloadUrl]) @if($downloadUrl) onclick="window.location.href='{{ $downloadUrl }}'" @endif>
                                    <td>{{ $entry['label'] }}</td>
                                    <td dir="ltr">
                                        <a href="{{ $downloadUrl }}" class="admin-payment-log-viewer__file-link" onclick="event.stopPropagation()">{{ $entry['basename'] }}</a>
                                    </td>
                                    <td dir="ltr">{{ $this->formatSize($entry['size_bytes']) }}</td>
                                    <td>
                                        <x-filament::button tag="a" href="{{ $downloadUrl }}" size="sm" color="gray" class="admin-payment-log-viewer__download-btn">
                                            دانلود ZIP
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
