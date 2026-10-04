<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Payment;
use App\Services\Payment\PaymentLogReader;
use App\Support\AdminAccess;
use Filament\Pages\Page;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class ViewPaymentLogs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'لاگ فایل پرداخت';

    protected static ?string $slug = 'payment-logs';

    protected static ?string $title = 'رهگیری لاگ فایل پرداخت‌ها';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.payment-log-viewer';

    #[Url(as: 'tracking')]
    public string $trackingCode = '';

    #[Url(as: 'date')]
    public ?string $logDate = null;

    /** @var list<string> */
    public array $logLines = [];

    /** @var list<string> */
    public array $scannedFiles = [];

    public bool $truncated = false;

    public ?string $paymentUrl = null;

    public ?string $searchMessage = null;

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function mount(): void
    {
        if ($this->logDate === null || $this->logDate === '') {
            $this->logDate = now()->format('Y-m-d');
        }

        if (trim($this->trackingCode) !== '') {
            $this->search();
        }
    }

    public function search(PaymentLogReader $reader): void
    {
        $this->validate([
            'trackingCode' => ['required', 'string', 'min:4', 'max:32'],
            'logDate' => ['nullable', 'date_format:Y-m-d'],
        ], [
            'trackingCode.required' => 'کد رهگیری پرداخت را وارد کنید.',
        ]);

        $code = strtoupper(trim($this->trackingCode));
        $this->trackingCode = $code;

        $payment = Payment::query()->where('tracking_code', $code)->first();
        $this->paymentUrl = $payment
            ? PaymentResource::getUrl('view', ['record' => $payment->id])
            : null;

        $result = $reader->searchByTrackingCode(
            $code,
            $this->logDate !== '' ? $this->logDate : null,
            daySpan: $this->logDate !== '' ? 1 : 7,
        );

        $this->logLines = $result['lines'];
        $this->scannedFiles = $result['scanned_files'];
        $this->truncated = $result['truncated'];

        if ($this->logLines === []) {
            $this->searchMessage = $result['scanned_files'] === []
                ? 'فایل لاگ پرداخت برای تاریخ انتخاب‌شده روی سرور پیدا نشد (مسیر: storage/logs/payments-YYYY-MM-DD.log).'
                : 'ردیفی با این کد رهگیری در فایل(های) اسکن‌شده نیست. تاریخ دیگر را امتحان کنید یا «همه روزهای اخیر» را بزنید.';
        } else {
            $this->searchMessage = null;
        }
    }

    public function searchAllRecentDays(PaymentLogReader $reader): void
    {
        $this->validate([
            'trackingCode' => ['required', 'string', 'min:4', 'max:32'],
        ]);

        $this->logDate = '';
        $this->search($reader);
    }

    public function downloadUrlFor(string $absolutePath): ?string
    {
        $basename = app(PaymentLogReader::class)->safeBasenameFromAbsolutePath($absolutePath);

        if ($basename === null) {
            return null;
        }

        return route('filament.admin.payment-logs.download', ['file' => $basename]);
    }

    /** @return list<array{value: string, label: string}> */
    #[Computed]
    public function logDateOptions(): array
    {
        $reader = app(PaymentLogReader::class);
        $options = [['value' => '', 'label' => '۷ روز اخیر']];

        foreach ($reader->availableLogDates(30) as $date) {
            $options[] = [
                'value' => $date,
                'label' => $reader->formatDisplayDate($date),
            ];
        }

        return $options;
    }
}
