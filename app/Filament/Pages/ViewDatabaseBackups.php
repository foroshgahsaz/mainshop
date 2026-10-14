<?php

namespace App\Filament\Pages;

use App\Services\Backup\DatabaseBackupService;
use App\Services\Backup\FilesBackupService;
use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class ViewDatabaseBackups extends Page
{
    public const UI_VERSION = '2026-10-14-v2';

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'بک‌آپ دیتابیس';

    protected static ?string $slug = 'database-backups';

    protected static ?string $title = 'بک‌آپ دیتابیس و فایل‌ها';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.database-backups';

    /** @var list<array<string, mixed>> */
    public array $backupEntries = [];

    /** @var list<array<string, mixed>> */
    public array $filesBackupEntries = [];

    public string $backupDirectory = '';

    public string $filesBackupDirectory = '';

    public string $scheduleSummary = '';

    public string $filesScheduleSummary = '';

    public string $filesSourceSummary = '';

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function mount(DatabaseBackupService $databaseBackups, FilesBackupService $filesBackups): void
    {
        $this->refreshList($databaseBackups, $filesBackups);
    }

    public function runManualBackup(): void
    {
        try {
            app(DatabaseBackupService::class)->create(manual: true);
        } catch (Throwable $e) {
            Notification::make()
                ->title('خطا در بک‌آپ دستی دیتابیس')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->refreshList();

        Notification::make()
            ->title('بک‌آپ دستی دیتابیس با موفقیت ایجاد شد')
            ->success()
            ->send();
    }

    public function runManualFilesBackup(): void
    {
        try {
            app(FilesBackupService::class)->create(manual: true);
        } catch (Throwable $e) {
            Notification::make()
                ->title('خطا در بک‌آپ دستی فایل‌ها')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->refreshList();

        Notification::make()
            ->title('بک‌آپ دستی فایل‌ها با موفقیت ایجاد شد')
            ->success()
            ->send();
    }

    public function formatSize(?int $bytes): string
    {
        return app(DatabaseBackupService::class)->formatFileSize($bytes);
    }

    protected function refreshList(
        ?DatabaseBackupService $databaseBackups = null,
        ?FilesBackupService $filesBackups = null,
    ): void {
        $databaseBackups ??= app(DatabaseBackupService::class);
        $filesBackups ??= app(FilesBackupService::class);

        $this->backupDirectory = $databaseBackups->backupDirectory();
        $this->backupEntries = $databaseBackups->listBackups($databaseBackups->keepCount());

        $time = (string) config('backup.database.schedule_time', '18:00');
        $tz = (string) config('backup.database.schedule_timezone', 'Asia/Tehran');
        $this->scheduleSummary = "هر روز ساعت {$time} ({$tz}) — نگه‌داری {$databaseBackups->keepCount()} نسخه آخر";

        $this->filesBackupDirectory = $filesBackups->backupDirectory();
        $this->filesBackupEntries = $filesBackups->listBackups($filesBackups->keepCount());

        $filesTime = (string) config('backup.files.schedule_time', '19:00');
        $filesTz = (string) config('backup.files.schedule_timezone', 'Asia/Tehran');
        $this->filesScheduleSummary = "هر روز ساعت {$filesTime} ({$filesTz}) — فقط {$filesBackups->keepCount()} نسخه آخر";

        $roots = $filesBackups->sourceRoots();
        $this->filesSourceSummary = $roots !== []
            ? implode('، ', $roots)
            : '—';
    }
}
