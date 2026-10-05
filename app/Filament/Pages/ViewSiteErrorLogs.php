<?php

namespace App\Filament\Pages;

use App\Services\Logging\ApplicationLogReader;
use App\Support\AdminAccess;
use Filament\Pages\Page;

class ViewSiteErrorLogs extends Page
{
    public const UI_VERSION = '2026-10-05-v1';

    protected static ?string $navigationIcon = 'heroicon-o-bug-ant';

    protected static ?string $navigationLabel = 'لاگ خطای سایت';

    protected static ?string $slug = 'site-error-logs';

    protected static ?string $title = 'لاگ خطا و برنامه (Laravel)';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.site-error-log-viewer';

    /** @var list<array<string, mixed>> */
    public array $recentEntries = [];

    /** @var array<string, mixed>|null */
    public ?array $legacyLogEntry = null;

    public string $logsDirectory = '';

    /** @var list<string> */
    public array $errorPreviewLines = [];

    public ?string $errorPreviewSource = null;

    public bool $errorPreviewTruncated = false;

    public string $searchQuery = '';

    /** @var list<string> */
    public array $searchLines = [];

    public bool $searchTruncated = false;

    /** @var list<string> */
    public array $searchScannedFiles = [];

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function mount(ApplicationLogReader $reader): void
    {
        $this->logsDirectory = $reader->logDirectory();
        $this->recentEntries = $reader->recentDayEntries(ApplicationLogReader::RECENT_DAYS);
        $this->legacyLogEntry = $reader->legacySingleLogEntry();

        $preview = $reader->tailRecentErrors();
        $this->errorPreviewLines = $preview['lines'];
        $this->errorPreviewSource = $preview['source_basename'];
        $this->errorPreviewTruncated = $preview['truncated'];

        $q = request()->query('q');
        if (is_string($q) && trim($q) !== '') {
            $this->searchQuery = trim($q);
            $search = $reader->searchRecent($this->searchQuery, 7);
            $this->searchLines = $search['lines'];
            $this->searchTruncated = $search['truncated'];
            $this->searchScannedFiles = $search['scanned_files'];
        }
    }

    public function formatSize(?int $bytes): string
    {
        return app(ApplicationLogReader::class)->formatFileSize($bytes);
    }
}
