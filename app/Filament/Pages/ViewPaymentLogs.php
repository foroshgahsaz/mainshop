<?php

namespace App\Filament\Pages;

use App\Services\Payment\PaymentLogReader;
use App\Services\Payment\PaymentLogTimelineParser;
use App\Support\AdminAccess;
use Filament\Pages\Page;

class ViewPaymentLogs extends Page
{
    public const UI_VERSION = '2026-10-28-timeline-v1';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'لاگ پرداخت (تایم‌لاین)';

    protected static ?string $slug = 'payment-logs';

    protected static ?string $title = 'لاگ پرداخت — تایم‌لاین و فایل';

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

    public string $persistenceSummary = '';

    /** @var list<array<string, mixed>> */
    public array $directoryDiagnostics = [];

    public string $searchQuery = '';

    /** @var list<array<string, mixed>> */
    public array $timelineEvents = [];

    public bool $searchTruncated = false;

    /** @var list<string> */
    public array $searchScannedFiles = [];

    public ?string $searchPrimaryFile = null;

    public string $searchReferenceKind = '';

    public function mount(PaymentLogReader $reader, PaymentLogTimelineParser $parser): void
    {
        $this->logsDirectory = $reader->logDirectory();
        $this->persistenceSummary = $reader->persistenceSummary();
        $this->directoryDiagnostics = $reader->directoryDiagnostics();
        $this->recentEntries = $reader->recentDayEntries();
        $this->archiveEntries = $reader->archivedZipEntries();
        $this->legacyLogEntry = $reader->legacySingleLogEntry();

        $q = request()->query('tracking') ?? request()->query('q') ?? request()->query('payment_id');
        if (is_string($q) && trim($q) !== '') {
            $this->searchQuery = trim($q);
            $search = $reader->searchByReference($this->searchQuery, null, 14);
            $this->searchTruncated = $search['truncated'];
            $this->searchScannedFiles = $search['scanned_files'];
            $this->searchPrimaryFile = $search['primary_file'];
            $this->searchReferenceKind = (string) ($search['reference_kind'] ?? '');

            $events = $parser->parseLines($search['lines']);
            $this->timelineEvents = array_map(function (array $event) use ($parser): array {
                $event['gateway_payload'] = $parser->formatGatewayPayloadForDisplay(
                    $event['gateway_raw'] ?? null,
                    $event['context'] ?? [],
                    $event['code'] ?? ''
                );
                $event['show_gateway_button'] = ($event['gateway_payload'] ?? '') !== ''
                    || ($event['has_gateway_payload'] ?? false);

                return $event;
            }, $events);
        }
    }

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function formatSize(?int $bytes): string
    {
        return app(PaymentLogReader::class)->formatFileSize($bytes);
    }

    public function timelineTone(array $event): string
    {
        $code = (string) ($event['code'] ?? '');

        if (preg_match('/(_failed|_exception|reversal_failed|verify_exception|callback_verify_exception)/', $code)) {
            return 'fail';
        }

        if (preg_match('/(canceled|duplicate_payment)/', $code)) {
            return 'warn';
        }

        if ((int) ($event['progress'] ?? 0) >= 100) {
            return 'ok';
        }

        if (preg_match('/(token_received|authority_received|ticket_created|order_created|verify_gateway_result)/', $code)) {
            $context = is_array($event['context'] ?? null) ? $event['context'] : [];
            if (array_key_exists('successful', $context) && $context['successful'] === false) {
                return 'fail';
            }

            return 'ok';
        }

        return 'neutral';
    }
}
