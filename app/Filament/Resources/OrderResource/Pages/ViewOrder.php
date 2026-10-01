<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\FreightCarrier;
use App\Models\OrderNote;
use App\Services\Order\OrderActivityLogger;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Representative\RepresentativeOrderAdminService;
use App\Services\Representative\RepresentativeProformaService;
use App\Support\AdminAccess;
use App\Support\Order\OrderItemLinePricing;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected static string $view = 'filament.orders.view-order';

    public string $newNote = '';

    public string $newNoteType = OrderNote::TYPE_PRIVATE;

    public string $editStatus = '';

    public string $editTracking = '';

    public ?int $editFreightCarrierId = null;

    public string $editPaymentGateway = 'zarinpal';

    /** @var array<int, int> */
    public array $editItemQuantities = [];

    /** @var array<int, string> */
    public array $editItemDiscountTypes = [];

    /** @var array<int, int> */
    public array $editItemDiscountValues = [];

    /** @var list<array{id: int|null, kind: string, title: string, amount: int|string}> */
    public array $editInvoiceLines = [];

    public bool $showInvoiceLineModal = false;

    public ?int $invoiceLineModalIndex = null;

    public string $modalInvoiceLineKind = 'fee';

    public string $modalInvoiceLineTitle = '';

    public string $modalInvoiceLineAmount = '';

    public bool $showItemDiscountModal = false;

    public ?int $itemDiscountModalItemId = null;

    public string $modalItemDiscountType = 'none';

    public int $modalItemDiscountValue = 0;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->syncFormFields();
        $this->refreshRecord();
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\Action::make('back')
                ->label('لیست سفارش‌ها')
                ->icon('heroicon-o-arrow-right')
                ->url(OrderResource::getUrl('index'))
                ->color('gray'),
        ];

        $order = $this->record;
        if (
            $order->isRepresentativeOrder()
            && ($order->isDraft() || $order->isProforma())
        ) {
            $actions[] = Actions\Action::make('downloadProformaPdf')
                ->label('PDF پیش‌فاکتور')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('representative.proforma.pdf', $order))
                ->openUrlInNewTab();
        }

        if ($order->isProforma() && $order->hasActiveStockReservation()) {
            $actions[] = Actions\Action::make('extendProformaReservation')
                ->label('تمدید رزرو موجودی')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('مهلت رزرو موجودی این پیش‌فاکتور تمدید می‌شود.')
                ->action(function () use ($order): void {
                    $minutes = (int) config('shop.representative.proforma_reservation_extend_minutes', 1440);
                    app(RepresentativeProformaService::class)->extendReservation(
                        $order->fresh(),
                        $minutes,
                        auth()->user(),
                    );
                    $this->refreshRecord();
                    Notification::make()->title('مهلت رزرو تمدید شد')->success()->send();
                });
        }

        return $actions;
    }

    public function getTitle(): string
    {
        return 'سفارش #'.$this->record->tracking_code;
    }

    public function addNote(OrderActivityLogger $logger): void
    {
        if (! AdminAccess::canManageShopInAdmin()) {
            return;
        }

        $this->validate([
            'newNote' => ['required', 'string', 'max:2000'],
            'newNoteType' => ['required', 'in:private,customer'],
        ]);

        $logger->byUser($this->record, auth()->user(), trim($this->newNote), $this->newNoteType);
        $this->newNote = '';
        $this->refreshRecord();

        Notification::make()->title('یادداشت ثبت شد')->success()->send();
    }

    public function saveOrderMeta(OrderService $orders): void
    {
        if (! AdminAccess::canManageShopInAdmin()) {
            return;
        }

        $this->validate([
            'editStatus' => ['required', 'string'],
            'editTracking' => ['nullable', 'string', 'max:100'],
        ]);

        $order = $this->record;
        $actor = auth()->user();

        if ($this->editStatus !== $order->status) {
            $order = $orders->updateStatus($order, $this->editStatus, $actor);
        }

        $currentTracking = (string) ($order->shipping_tracking_code ?? '');
        if ($this->editTracking !== $currentTracking) {
            $order = $orders->updateTracking($order, $this->editTracking ?: null, $actor);
        }

        $order = $order->fresh();

        if (
            $order->isRepresentativeOrder()
            && ($order->isDraft() || $order->isProforma())
        ) {
            app(RepresentativeOrderAdminService::class)->updateFulfillment(
                $order,
                $this->editFreightCarrierId,
                $this->editPaymentGateway,
                $actor,
            );
        }

        $this->refreshRecord();
        $this->syncFormFields();

        Notification::make()->title('سفارش به‌روزرسانی شد')->success()->send();
    }

    public function saveProformaItems(RepresentativeOrderAdminService $admin): void
    {
        $this->persistProformaItemsToDatabase($admin);

        Notification::make()->title('اقلام پیش‌فاکتور به‌روزرسانی شد')->success()->send();
    }

    public function saveInvoiceLines(RepresentativeOrderAdminService $admin): void
    {
        $this->persistInvoiceLinesToDatabase($admin);
    }

    protected function persistProformaItemsToDatabase(?RepresentativeOrderAdminService $admin = null): void
    {
        if (! $this->canAdminEditProforma) {
            throw ValidationException::withMessages([
                'order' => 'در حال حاضر ویرایش اقلام پیش‌فاکتور مجاز نیست.',
            ]);
        }

        $this->validate([
            'editItemQuantities' => ['required', 'array'],
            'editItemQuantities.*' => ['required', 'integer', 'min:1', 'max:999'],
            'editItemDiscountTypes' => ['required', 'array'],
            'editItemDiscountValues' => ['required', 'array'],
        ]);

        ($admin ?? app(RepresentativeOrderAdminService::class))->syncProformaItems(
            $this->record->fresh(['items']),
            $this->editItemQuantities,
            $this->editItemDiscountTypes,
            $this->editItemDiscountValues,
            auth()->user(),
        );

        $this->refreshRecord();
        $this->syncFormFields();
    }

    protected function persistInvoiceLinesToDatabase(?RepresentativeOrderAdminService $admin = null): void
    {
        if (! $this->canAdminEditProforma) {
            throw ValidationException::withMessages([
                'order' => 'در حال حاضر ویرایش ردیف‌های فاکتور مجاز نیست.',
            ]);
        }

        ($admin ?? app(RepresentativeOrderAdminService::class))->syncInvoiceLines(
            $this->record->fresh(['invoiceLines']),
            $this->editInvoiceLines,
            auth()->user(),
        );

        $this->refreshRecord();
        $this->syncFormFields();

        Notification::make()->title('ردیف‌های فاکتور ذخیره شد')->success()->send();
    }

    public function openInvoiceLineModal(?int $index = null): void
    {
        $this->invoiceLineModalIndex = $index;

        if ($index !== null && isset($this->editInvoiceLines[$index])) {
            $line = $this->editInvoiceLines[$index];
            $this->modalInvoiceLineKind = (string) $line['kind'];
            $this->modalInvoiceLineTitle = (string) $line['title'];
            $this->modalInvoiceLineAmount = (string) $line['amount'];
        } else {
            $this->modalInvoiceLineKind = 'fee';
            $this->modalInvoiceLineTitle = '';
            $this->modalInvoiceLineAmount = '';
        }

        $this->showInvoiceLineModal = true;
    }

    public function closeInvoiceLineModal(): void
    {
        $this->showInvoiceLineModal = false;
        $this->invoiceLineModalIndex = null;
    }

    public function confirmInvoiceLineModal(): void
    {
        $this->validate([
            'modalInvoiceLineKind' => ['required', 'in:fee,order_discount'],
            'modalInvoiceLineTitle' => ['required', 'string', 'max:200'],
            'modalInvoiceLineAmount' => ['required', 'integer', 'min:1'],
        ], [], [
            'modalInvoiceLineKind' => 'نوع',
            'modalInvoiceLineTitle' => 'شرح',
            'modalInvoiceLineAmount' => 'مبلغ',
        ]);

        $payload = [
            'id' => $this->invoiceLineModalIndex !== null
                ? ($this->editInvoiceLines[$this->invoiceLineModalIndex]['id'] ?? null)
                : null,
            'kind' => $this->modalInvoiceLineKind,
            'title' => trim($this->modalInvoiceLineTitle),
            'amount' => (int) $this->modalInvoiceLineAmount,
        ];

        if ($this->invoiceLineModalIndex !== null) {
            $this->editInvoiceLines[$this->invoiceLineModalIndex] = $payload;
        } else {
            $this->editInvoiceLines[] = $payload;
        }

        $this->closeInvoiceLineModal();

        $this->persistInvoiceLinesToDatabase();
    }

    public function openItemDiscountModal(int $itemId): void
    {
        $this->itemDiscountModalItemId = $itemId;
        $this->modalItemDiscountType = (string) ($this->editItemDiscountTypes[$itemId] ?? 'none');
        $this->modalItemDiscountValue = (int) ($this->editItemDiscountValues[$itemId] ?? 0);
        $this->showItemDiscountModal = true;
    }

    public function closeItemDiscountModal(): void
    {
        $this->showItemDiscountModal = false;
        $this->itemDiscountModalItemId = null;
    }

    public function confirmItemDiscountModal(): void
    {
        if ($this->itemDiscountModalItemId === null) {
            return;
        }

        $this->validate([
            'modalItemDiscountType' => ['required', 'in:none,fixed,percent'],
            'modalItemDiscountValue' => ['required', 'integer', 'min:0'],
        ]);

        $type = $this->modalItemDiscountType;
        $value = (int) $this->modalItemDiscountValue;

        if ($type === 'none') {
            $value = 0;
        } elseif ($type === 'percent' && ($value < 1 || $value > 100)) {
            throw ValidationException::withMessages([
                'modalItemDiscountValue' => 'درصد تخفیف باید بین ۱ تا ۱۰۰ باشد.',
            ]);
        } elseif ($type === 'fixed' && $value < 1) {
            throw ValidationException::withMessages([
                'modalItemDiscountValue' => 'مبلغ تخفیف باید بزرگ‌تر از صفر باشد.',
            ]);
        }

        $itemId = $this->itemDiscountModalItemId;
        $this->editItemDiscountTypes[$itemId] = $type;
        $this->editItemDiscountValues[$itemId] = $value;

        $this->closeItemDiscountModal();

        $this->persistProformaItemsToDatabase();

        Notification::make()->title('تخفیف ردیف ذخیره شد')->success()->send();
    }

    public function previewItemLineTotal(int $itemId): int
    {
        $item = $this->record->items->firstWhere('id', $itemId);

        if ($item === null) {
            return 0;
        }

        $preview = $item->replicate();
        $preview->quantity = (int) ($this->editItemQuantities[$itemId] ?? $item->quantity);
        $preview->line_discount_type = (string) ($this->editItemDiscountTypes[$itemId] ?? $item->line_discount_type ?? OrderItemLinePricing::DISCOUNT_NONE);
        $preview->line_discount_value = (int) ($this->editItemDiscountValues[$itemId] ?? $item->line_discount_value ?? 0);

        return OrderItemLinePricing::netAmount($preview);
    }

    public function removeInvoiceLineRow(int $index): void
    {
        if (! array_key_exists($index, $this->editInvoiceLines)) {
            return;
        }

        unset($this->editInvoiceLines[$index]);
        $this->editInvoiceLines = array_values($this->editInvoiceLines);

        $this->persistInvoiceLinesToDatabase();
    }

    public function getCanAdminEditProformaProperty(): bool
    {
        $order = $this->record;

        return AdminAccess::canManageShopInAdmin()
            && $order->isRepresentativeOrder()
            && $order->isProforma()
            && ! $order->isPaid();
    }

    protected function syncFormFields(): void
    {
        $this->editStatus = $this->record->status;
        $this->editTracking = (string) ($this->record->shipping_tracking_code ?? '');
        $this->editFreightCarrierId = $this->record->freight_carrier_id;
        $enabled = app(PaymentGatewayCatalog::class)->enabledNames();
        $this->editPaymentGateway = (string) ($this->record->payment_method ?: ($enabled[0] ?? 'zarinpal'));

        $this->editItemQuantities = [];
        $this->editItemDiscountTypes = [];
        $this->editItemDiscountValues = [];

        foreach ($this->record->items as $item) {
            $this->editItemQuantities[$item->id] = (int) $item->quantity;
            $this->editItemDiscountTypes[$item->id] = (string) ($item->line_discount_type ?? 'none');
            $this->editItemDiscountValues[$item->id] = (int) ($item->line_discount_value ?? 0);
        }

        $this->editInvoiceLines = $this->record->invoiceLines
            ->map(fn ($line) => [
                'id' => $line->id,
                'kind' => $line->kind,
                'title' => $line->title,
                'amount' => $line->amount,
            ])
            ->values()
            ->all();
    }

    /** @return Collection<int, FreightCarrier> */
    public function getFreightCarrierOptionsProperty()
    {
        return FreightCarrier::query()
            ->with(['province', 'city'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** @return list<array<string, mixed>> */
    public function getPaymentGatewayOptionsProperty(): array
    {
        return app(PaymentGatewayCatalog::class)->enabled();
    }

    protected function refreshRecord(): void
    {
        $this->record = $this->record->fresh([
            'user',
            'address',
            'items.product',
            'items.variant',
            'shippingMethod',
            'freightCarrier.province',
            'freightCarrier.city',
            'coupon',
            'payments.paidByRepresentative',
            'payments.user',
            'payments.notes.author',
            'notes.author',
            'invoiceLines',
        ]);
    }
}
