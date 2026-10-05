<x-filament-panels::page>
    <div class="admin-payment-log-viewer database-backup-page" data-database-backups data-ui-version="{{ \App\Filament\Pages\ViewDatabaseBackups::UI_VERSION }}">
        <section class="admin-order__section database-backup-manual-card">
            <h2 class="admin-order__section-title">بک‌آپ دستی</h2>
            <p class="admin-order__hint">
                برای گرفتن نسخه فوری از دیتابیس (مثلاً قبل از تغییر مهم) از دکمه زیر استفاده کنید.
                این بک‌آپ در لیست با برچسب <strong>دستی</strong> مشخص می‌شود.
            </p>
            <div class="database-backup-manual-card__actions">
                <x-filament::button
                    color="warning"
                    outlined
                    icon="heroicon-o-arrow-down-tray"
                    wire:click="runManualBackup"
                    wire:confirm="یک بک‌آپ دستی از دیتابیس ساخته می‌شود و در لیست با برچسب «دستی» نمایش داده می‌شود. فقط ۲ نسخه آخر نگه داشته می‌شود. ادامه می‌دهید؟"
                    wire:loading.attr="disabled"
                    class="database-backup-manual-btn"
                >
                    بک‌آپ دستی دیتابیس
                </x-filament::button>
            </div>
        </section>

        <section class="admin-order__section">
            <h2 class="admin-order__section-title">۲ بک‌آپ آخر دیتابیس</h2>
            <p class="admin-order__hint">
                مسیر ذخیره: <code dir="ltr">{{ $backupDirectory }}</code>
                — {{ $scheduleSummary }}
            </p>

            <div class="admin-payment-log-viewer__table-wrap">
                <table class="admin-payment-log-viewer__table">
                    <thead>
                        <tr>
                            <th>زمان</th>
                            <th>نوع</th>
                            <th>نام فایل</th>
                            <th>حجم</th>
                            <th>دانلود</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($backupEntries as $entry)
                            <tr @class(['database-backup-row--manual' => ($entry['kind'] ?? '') === 'manual'])>
                                <td>{{ $entry['created_at'] }}</td>
                                <td>
                                    @if (($entry['kind'] ?? '') === 'manual')
                                        <span class="admin-payment-log-viewer__badge database-backup-badge--manual">دستی</span>
                                    @else
                                        <span class="admin-payment-log-viewer__badge admin-payment-log-viewer__badge--zip">خودکار</span>
                                    @endif
                                </td>
                                <td dir="ltr" class="admin-payment-log-viewer__filename">{{ $entry['basename'] }}</td>
                                <td dir="ltr">{{ $this->formatSize($entry['size_bytes']) }}</td>
                                <td>
                                    <x-filament::button tag="a" href="{{ $entry['download_url'] }}" size="sm">
                                        دانلود
                                    </x-filament::button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="admin-payment-log-viewer__empty">هنوز بک‌آپی ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
