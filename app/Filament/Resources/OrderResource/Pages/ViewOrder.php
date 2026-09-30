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

    public function saveProformaItemQuantities(RepresentativeOrderAdminService $admin): void
    {
        if (! $this->canAdminEditProforma) {
            throw ValidationException::withMessages([
                'order' => 'در حال حاضر ویرایش اقلام پیش‌فاکتور مجاز نیست.',
            ]);
        }

        $this->validate([
            'editItemQuantities' => ['required', 'array'],
            'editItemQuantities.*' => ['required', 'integer', 'min:1', 'max:999'],
        ]);

        $admin->syncProformaItemQuantities(
            $this->record->fresh(['items']),
            $this->editItemQuantities,
            auth()->user(),
        );

        $this->refreshRecord();
        $this->syncFormFields();

        Notification::make()->title('تعداد اقلام به‌روزرسانی شد')->success()->send();
    }

    public function getCanAdminEditProformaProperty(): bool
    {
        $order = $this->record;

        return $order->isRepresentativeOrder()
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
        foreach ($this->record->items as $item) {
            $this->editItemQuantities[$item->id] = (int) $item->quantity;
        }
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
        ]);
    }
}
