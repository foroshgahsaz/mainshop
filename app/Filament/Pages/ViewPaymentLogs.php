<?php

namespace App\Filament\Pages;

use App\Services\Payment\PaymentLogArchiveService;
use App\Services\Payment\PaymentLogReader;
use App\Support\AdminAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;

class ViewPaymentLogs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'لاگ فایل پرداخت';

    protected static ?string $slug = 'payment-logs';

    protected static ?string $title = 'فایل‌های لاگ پرداخت';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.payment-log-viewer';

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function mount(PaymentLogArchiveService $archive): void
    {
        Cache::remember('payment_logs_archive_tick', now()->addHour(), function () use ($archive): bool {
            $archive->archiveLogsOlderThanRetention();

            return true;
        });
    }

    /** @return list<array<string, mixed>> */
    #[Computed]
    public function recentEntries(): array
    {
        return app(PaymentLogReader::class)->recentDayEntries(PaymentLogReader::RECENT_DAYS);
    }

    /** @return list<array<string, mixed>> */
    #[Computed]
    public function archiveEntries(): array
    {
        return app(PaymentLogReader::class)->archivedZipEntries();
    }

    public function formatSize(?int $bytes): string
    {
        return app(PaymentLogReader::class)->formatFileSize($bytes);
    }

    /** @return array<string, mixed>|null */
    #[Computed]
    public function legacyLogEntry(): ?array
    {
        return app(PaymentLogReader::class)->legacySingleLogEntry();
    }
}
