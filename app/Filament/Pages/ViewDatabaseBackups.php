<?php

namespace App\Filament\Pages;

use App\Services\Backup\DatabaseBackupService;
use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class ViewDatabaseBackups extends Page
{
    public const UI_VERSION = '2026-10-05-v1';

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'بک‌آپ دیتابیس';

    protected static ?string $slug = 'database-backups';

    protected static ?string $title = 'بک‌آپ دیتابیس';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.database-backups';

    /** @var list<array<string, mixed>> */
    public array $backupEntries = [];

    public string $backupDirectory = '';

    public string $scheduleSummary = '';

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function mount(DatabaseBackupService $backups): void
    {
        $this->refreshList($backups);
    }

    public function runManualBackup(): void
    {
        try {
            app(DatabaseBackupService::class)->create(manual: true);
        } catch (Throwable $e) {
            Notification::make()
                ->title('خطا در بک‌آپ دستی')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->refreshList();

        Notification::make()
            ->title('بک‌آپ دستی با موفقیت ایجاد شد')
            ->success()
            ->send();
    }

    public function formatSize(?int $bytes): string
    {
        return app(DatabaseBackupService::class)->formatFileSize($bytes);
    }

    protected function refreshList(?DatabaseBackupService $backups = null): void
    {
        $backups ??= app(DatabaseBackupService::class);

        $this->backupDirectory = $backups->backupDirectory();
        $this->backupEntries = $backups->listBackups($backups->keepCount());
        $time = (string) config('backup.database.schedule_time', '18:00');
        $tz = (string) config('backup.database.schedule_timezone', 'Asia/Tehran');
        $this->scheduleSummary = "هر روز ساعت {$time} ({$tz}) — نگه‌داری {$backups->keepCount()} نسخه آخر";
    }
}
