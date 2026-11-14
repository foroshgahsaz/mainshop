<?php

namespace App\Filament\Pages;

use App\Services\Catalog\BankdarCatalogService;
use App\Services\Product\ProductExcelImportParser;
use App\Services\Product\ProductListImportService;
use App\Support\AdminAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ProductListImport extends Page
{
    use WithFileUploads;

    private const SUCCESS_LOG_LIMIT = 200;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'ورود لیست محصول';

    protected static ?string $slug = 'product-list-import';

    protected static ?string $title = 'ورود لیست محصول';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.product-list-import';

    public $importFile = null;

    public bool $updateExistingBySku = true;

    public ?int $defaultBrandId = null;

    public ?int $defaultCategoryId = null;

    public bool $importRunning = false;

    public bool $importFinished = false;

    public int $importTotal = 0;

    public int $importProgress = 0;

    public string $importCurrentName = '';

    public int $importCreated = 0;

    public int $importUpdated = 0;

    /** @var list<string> */
    public array $importFailures = [];

    /** @var list<array{name: string, action: string}> */
    public array $importSuccesses = [];

    public int $importSuccessOverflow = 0;

    public static function canAccess(): bool
    {
        return AdminAccess::canImportProductsInAdmin();
    }

    public function beginImport(ProductExcelImportParser $parser): void
    {
        if ($this->importRunning) {
            return;
        }

        $this->resetImportUi();

        $rules = [
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:10240'],
        ];
        $labels = ['importFile' => 'فایل اکسل'];

        if (AdminAccess::isBankdarOnly()) {
            $rules['defaultBrandId'] = ['required', 'integer'];
            $rules['defaultCategoryId'] = ['required', 'integer'];
            $labels['defaultBrandId'] = 'برند';
            $labels['defaultCategoryId'] = 'دسته‌بندی';
        }

        $this->validate($rules, [], $labels);

        if (AdminAccess::isBankdarOnly()) {
            $catalog = app(BankdarCatalogService::class);
            $bankdarId = (int) auth()->id();
            if (! in_array((int) $this->defaultBrandId, $catalog->brandIdsForBankdar($bankdarId), true)
                || ! in_array((int) $this->defaultCategoryId, $catalog->categoryIdsForBankdar($bankdarId), true)
            ) {
                Notification::make()
                    ->title('برند یا دسته خارج از محدوده شماست')
                    ->danger()
                    ->send();

                return;
            }
        }

        /** @var TemporaryUploadedFile $upload */
        $upload = $this->importFile;
        $path = $upload->getRealPath();

        if (! is_string($path) || $path === '') {
            Notification::make()->title('فایل نامعتبر')->danger()->send();

            return;
        }

        $parsed = $parser->parseFile($path);
        $rows = $parsed['rows'];
        $parseErrors = $parsed['errors'];

        if ($rows === [] && $parseErrors !== []) {
            Notification::make()
                ->title('خواندن فایل ناموفق')
                ->body(implode("\n", array_slice($parseErrors, 0, 5)))
                ->danger()
                ->send();

            return;
        }

        if ($rows === []) {
            Notification::make()
                ->title('ردیفی برای import یافت نشد')
                ->warning()
                ->send();

            return;
        }

        $this->importFailures = $parseErrors;
        $this->importTotal = count($rows);
        $this->importRunning = true;

        Cache::put($this->importCacheKey(), [
            'rows' => $rows,
            'index' => 0,
            'update_existing' => $this->updateExistingBySku,
            'default_brand_id' => $this->defaultBrandId,
            'default_category_id' => $this->defaultCategoryId,
        ], now()->addHour());

        $this->importCurrentName = $rows[0]['name'] ?? '';

        $this->processImportTick(app(ProductListImportService::class));
    }

    public function processImportTick(ProductListImportService $importer): void
    {
        if (! $this->importRunning) {
            return;
        }

        $state = Cache::get($this->importCacheKey());

        if (! is_array($state) || ! isset($state['rows'], $state['index'])) {
            $this->abortImport('نشست import منقضی شد. دوباره فایل را انتخاب کنید.');

            return;
        }

        /** @var list<array{sku: string, name: string, stock: int, price: int}> $rows */
        $rows = $state['rows'];
        $index = (int) $state['index'];
        $updateExisting = (bool) ($state['update_existing'] ?? true);
        $defaultBrandId = isset($state['default_brand_id']) ? (int) $state['default_brand_id'] : null;
        $defaultCategoryId = isset($state['default_category_id']) ? (int) $state['default_category_id'] : null;

        if ($index >= count($rows)) {
            $this->finishImport();

            return;
        }

        $row = $rows[$index];
        $this->importCurrentName = $row['name'];
        $line = $index + 2;

        $result = $importer->importRow(
            $row,
            $line,
            auth()->id(),
            $updateExisting,
            $defaultBrandId ?: null,
            $defaultCategoryId ?: null,
        );

        if ($result['status'] === 'created') {
            $this->importCreated++;
            $this->pushSuccess($row['name'], 'جدید');
        } elseif ($result['status'] === 'updated') {
            $this->importUpdated++;
            $this->pushSuccess($row['name'], 'به‌روزرسانی');
        } elseif ($result['status'] === 'error' && isset($result['message'])) {
            $this->importFailures[] = $result['message'];
        }

        $index++;
        $state['index'] = $index;
        Cache::put($this->importCacheKey(), $state, now()->addHour());

        $this->importProgress = $index;

        if ($index >= count($rows)) {
            $this->finishImport();

            return;
        }

        $this->importCurrentName = $rows[$index]['name'];
    }

    protected function pushSuccess(string $name, string $action): void
    {
        if (count($this->importSuccesses) < self::SUCCESS_LOG_LIMIT) {
            $this->importSuccesses[] = ['name' => $name, 'action' => $action];

            return;
        }

        $this->importSuccessOverflow++;
    }

    protected function finishImport(): void
    {
        $this->importRunning = false;
        $this->importFinished = true;
        $this->importCurrentName = '';
        Cache::forget($this->importCacheKey());
        $this->reset('importFile');
    }

    protected function abortImport(string $message): void
    {
        $this->importRunning = false;
        Cache::forget($this->importCacheKey());
        Notification::make()->title($message)->danger()->send();
    }

    protected function resetImportUi(): void
    {
        $this->importFinished = false;
        $this->importTotal = 0;
        $this->importProgress = 0;
        $this->importCurrentName = '';
        $this->importCreated = 0;
        $this->importUpdated = 0;
        $this->importFailures = [];
        $this->importSuccesses = [];
        $this->importSuccessOverflow = 0;
    }

    protected function importCacheKey(): string
    {
        return 'product-list-import:'.auth()->id();
    }

    public function getImportPercentProperty(): int
    {
        if ($this->importTotal <= 0) {
            return 0;
        }

        return (int) min(100, round(($this->importProgress / $this->importTotal) * 100));
    }

    /** @return array<int, string> */
    public function getBrandOptionsProperty(): array
    {
        if (! AdminAccess::isBankdarOnly()) {
            return [];
        }

        return app(BankdarCatalogService::class)->brandOptionsForBankdar((int) auth()->id());
    }

    /** @return array<int, string> */
    public function getCategoryOptionsProperty(): array
    {
        if (! AdminAccess::isBankdarOnly()) {
            return [];
        }

        return app(BankdarCatalogService::class)->categoryOptionsForBankdar((int) auth()->id());
    }
}
