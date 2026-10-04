<x-filament-panels::page>
    <div class="admin-payment-log-viewer" data-admin-payment-log>
        <section class="admin-order__section">
            <h2 class="admin-order__section-title">۲۰ روز اخیر</h2>
            <p class="admin-order__hint">
                فایل‌های روزانه در <code dir="ltr">storage/logs/payments-YYYY-MM-DD.log</code> ذخیره می‌شوند.
                لاگ‌های قدیمی‌تر از ۲۰ روز به‌صورت خودکار فشرده (<code dir="ltr">.log.zip</code>) می‌شوند.
            </p>

            @if ($this->legacyLogEntry)
                <p class="admin-order__hint admin-payment-log-viewer__legacy">
                    فایل قدیمی یک‌تکه:
                    <a href="{{ $this->legacyLogEntry['download_url'] }}" class="admin-order__link">{{ $this->legacyLogEntry['basename'] }}</a>
                    <span dir="ltr">({{ $this->formatSize($this->legacyLogEntry['size_bytes']) }})</span>
                </p>
            @endif

            <div class="admin-payment-log-viewer__table-wrap">
                <table class="admin-payment-log-viewer__table">
                    <thead>
                        <tr>
                            <th>تاریخ</th>
                            <th>وضعیت</th>
                            <th>حجم</th>
                            <th>دانلود</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->recentEntries as $entry)
                            <tr>
                                <td>{{ $entry['label'] }}</td>
                                <td>
                                    @if ($entry['kind'] === 'log')
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--ok">فایل روزانه</span>
                                    @elseif ($entry['kind'] === 'zip')
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--zip">فشرده</span>
                                    @else
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--empty">بدون فایل</span>
                                    @endif
                                </td>
                                <td dir="ltr">{{ $this->formatSize($entry['size_bytes']) }}</td>
                                <td>
                                    @if ($entry['download_url'])
                                        <a href="{{ $entry['download_url'] }}" class="admin-order__link admin-payment-log-viewer__download-btn">
                                            دانلود
                                        </a>
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

        @if ($this->archiveEntries !== [])
            <section class="admin-order__section">
                <h2 class="admin-order__section-title">آرشیو (بیش از ۲۰ روز)</h2>
                <p class="admin-order__hint">فایل‌های فشردهٔ روزهای قدیمی‌تر — همان نام تاریخ در نام فایل حفظ شده است.</p>

                <div class="admin-payment-log-viewer__table-wrap">
                    <table class="admin-payment-log-viewer__table">
                        <thead>
                            <tr>
                                <th>تاریخ</th>
                                <th>حجم</th>
                                <th>دانلود</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->archiveEntries as $entry)
                                <tr>
                                    <td>{{ $entry['label'] }}</td>
                                    <td dir="ltr">{{ $this->formatSize($entry['size_bytes']) }}</td>
                                    <td>
                                        @if ($entry['download_url'])
                                            <a href="{{ $entry['download_url'] }}" class="admin-order__link admin-payment-log-viewer__download-btn">
                                                دانلود ZIP
                                            </a>
                                        @endif
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
