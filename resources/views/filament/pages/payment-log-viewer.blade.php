<x-filament-panels::page>
    <div class="admin-payment-log-viewer" data-admin-payment-log data-ui-version="{{ \App\Filament\Pages\ViewPaymentLogs::UI_VERSION }}">
        <section class="admin-order__section">
            <h2 class="admin-order__section-title">۲۰ روز اخیر</h2>
            <p class="admin-order__hint">
                مسیر فعال (تنظیمات): <code dir="ltr">{{ $logsDirectory }}</code>
                — فایل روزانه: <code dir="ltr">payments-YYYY-MM-DD.log</code>
                — نسخه UI: <code dir="ltr">{{ \App\Filament\Pages\ViewPaymentLogs::UI_VERSION }}</code>
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
                            <tr>
                                <td>{{ $entry['label'] }}</td>
                                <td dir="ltr" class="admin-payment-log-viewer__filename">
                                    {{ $entry['basename'] ?? '—' }}
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
                                <tr>
                                    <td>{{ $entry['label'] }}</td>
                                    <td dir="ltr">{{ $entry['basename'] }}</td>
                                    <td dir="ltr">{{ $this->formatSize($entry['size_bytes']) }}</td>
                                    <td>
                                        <x-filament::button tag="a" href="{{ $entry['download_url'] }}" size="sm" color="gray">
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
