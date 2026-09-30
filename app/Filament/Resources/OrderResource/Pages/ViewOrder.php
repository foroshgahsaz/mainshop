<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderNote;
use App\Models\FreightCarrier;
use App\Services\Order\OrderActivityLogger;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentGatewayCatalog;
use App\Services\Representative\RepresentativeOrderAdminService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

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
            );
        }

        $this->refreshRecord();
        $this->syncFormFields();

        Notification::make()->title('سفارش به‌روزرسانی شد')->success()->send();
    }

    protected function syncFormFields(): void
    {
        $this->editStatus = $this->record->status;
        $this->editTracking = (string) ($this->record->shipping_tracking_code ?? '');
        $this->editFreightCarrierId = $this->record->freight_carrier_id;
        $enabled = app(PaymentGatewayCatalog::class)->enabledNames();
        $this->editPaymentGateway = (string) ($this->record->payment_method ?: ($enabled[0] ?? 'zarinpal'));
    }

    /** @return \Illuminate\Support\Collection<int, FreightCarrier> */
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
            'payments.notes.author',
            'notes.author',
        ]);
    }
}
