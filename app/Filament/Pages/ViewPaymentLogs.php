<?php

namespace App\Filament\Pages;

use App\Services\Payment\PaymentLogReader;
use App\Support\AdminAccess;
use Filament\Pages\Page;

class ViewPaymentLogs extends Page
{
    public const UI_VERSION = '2026-10-04-list-v2';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'لاگ فایل پرداخت';

    protected static ?string $slug = 'payment-logs';

    protected static ?string $title = 'فایل‌های لاگ پرداخت';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.payment-log-viewer';

    /** @var list<array<string, mixed>> */
    public array $recentEntries = [];

    /** @var list<array<string, mixed>> */
    public array $archiveEntries = [];

    /** @var array<string, mixed>|null */
    public ?array $legacyLogEntry = null;

    public string $logsDirectory = '';

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function mount(PaymentLogReader $reader): void
    {
        $this->logsDirectory = $reader->logDirectory();
        $this->recentEntries = $reader->recentDayEntries(PaymentLogReader::RECENT_DAYS);
        $this->archiveEntries = $reader->archivedZipEntries();
        $this->legacyLogEntry = $reader->legacySingleLogEntry();
    }

    public function formatSize(?int $bytes): string
    {
        return app(PaymentLogReader::class)->formatFileSize($bytes);
    }
}
