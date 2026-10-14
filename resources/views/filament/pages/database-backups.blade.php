<x-filament-panels::page>
    <div class="admin-payment-log-viewer database-backup-page" data-database-backups data-ui-version="{{ \App\Filament\Pages\ViewDatabaseBackups::UI_VERSION }}">
        <section class="admin-order__section database-backup-manual-card">
            <h2 class="admin-order__section-title">بک‌آپ دستی دیتابیس</h2>
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
                    wire:confirm="یک بک‌آپ دستی از دیتابیس ساخته می‌شود. فقط {{ app(\App\Services\Backup\DatabaseBackupService::class)->keepCount() }} نسخه آخر دیتابیس نگه داشته می‌شود. ادامه می‌دهید؟"
                    wire:loading.attr="disabled"
                    class="database-backup-manual-btn"
                >
                    بک‌آپ دستی دیتابیس
                </x-filament::button>
            </div>
        </section>

        <section class="admin-order__section">
            <h2 class="admin-order__section-title">بک‌آپ‌های دیتابیس</h2>
            <p class="admin-order__hint">
                مسیر ذخیره: <code dir="ltr">{{ $backupDirectory }}</code>
                — {{ $scheduleSummary }}
                <br>
                اگر روی سرور <code dir="ltr">mysqldump</code> نصب نباشد، بک‌آپ با اتصال PHP (PDO) ساخته می‌شود.
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

        <section class="admin-order__section database-backup-manual-card">
            <h2 class="admin-order__section-title">بک‌آپ دستی فایل‌ها</h2>
            <p class="admin-order__hint">
                آرشیو فشرده از فایل‌های آپلودشده (تصاویر و رسانه فروشگاه). پوشه‌های
                <code dir="ltr">database-backups</code>، <code dir="ltr">files-backups</code> و <code dir="ltr">logs</code>
                داخل دیسک مشترک در بک‌آپ گنجانده نمی‌شوند.
            </p>
            <div class="database-backup-manual-card__actions">
                <x-filament::button
                    color="warning"
                    outlined
                    icon="heroicon-o-archive-box-arrow-down"
                    wire:click="runManualFilesBackup"
                    wire:confirm="یک بک‌آپ دستی از فایل‌ها ساخته می‌شود. فقط ۱ نسخه آخر فایل‌ها نگه داشته می‌شود (نسخه قبلی حذف می‌شود). ادامه می‌دهید؟"
                    wire:loading.attr="disabled"
                    class="database-backup-manual-btn"
                >
                    بک‌آپ دستی فایل‌ها
                </x-filament::button>
            </div>
        </section>

        <section class="admin-order__section">
            <h2 class="admin-order__section-title">آخرین بک‌آپ فایل‌ها</h2>
            <p class="admin-order__hint">
                مسیر ذخیره: <code dir="ltr">{{ $filesBackupDirectory }}</code>
                — {{ $filesScheduleSummary }}
                <br>
                منبع: <code dir="ltr">{{ $filesSourceSummary }}</code>
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
                        @forelse ($filesBackupEntries as $entry)
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
                                <td colspan="5" class="admin-payment-log-viewer__empty">هنوز بک‌آپ فایلی ثبت نشده است.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
