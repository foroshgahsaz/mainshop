<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Pages\ViewPaymentLogs;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\PaymentResource;
use App\Filament\Support\PaymentDeleteActions;
use App\Models\Payment;
use App\Services\Order\OrderService;
use App\Services\Payment\PaymentActivityLogger;
use App\Services\Payment\BajetRefundService;
use App\Services\Payment\DigiPayRefundService;
use App\Services\Payment\TaraRefundService;
use App\Support\AdminAccess;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected static string $view = 'filament.payments.view-payment';

    public string $newNote = '';

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->refreshRecord();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirmWarehouseProcessing')
                ->label('تأیید پرداخت — پردازش انبار')
                ->icon('heroicon-o-building-storefront')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('انتقال سفارش به پردازش انبار')
                ->modalDescription('وضعیت سفارش به «پردازش انبار» تغییر می‌کند و در صورت فعال بودن قالب، پیامک به مشتری ارسال می‌شود.')
                ->visible(fn (): bool => AdminAccess::canManageShopInAdmin()
                    && $this->record->status === Payment::STATUS_SUCCESS
                    && $this->record->order !== null
                    && $this->record->order->canAdvanceToWarehouseProcessing())
                ->action(function (OrderService $orders): void {
                    try {
                        $order = $this->record->order;
                        if ($order === null) {
                            return;
                        }
                        $orders->advanceToWarehouseProcessing($order, auth()->user());
                        $this->refreshRecord();
                        Notification::make()->title('سفارش به پردازش انبار منتقل شد')->success()->send();
                    } catch (\RuntimeException $e) {
                        Notification::make()->title('انتقال ممکن نیست')->body($e->getMessage())->danger()->send();
                    }
                }),
            Actions\Action::make('taraRefund')
                ->label('مرجوعی تارا')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('مرجوعی پرداخت تارا')
                ->modalDescription('مبلغ این پرداخت در درگاه تارا مرجوع می‌شود. اگر سفارش دیگر تسویه نباشد، به حالت در انتظار برمی‌گردد.')
                ->visible(fn () => AdminAccess::canManageShopInAdmin()
                    && $this->record->gateway === 'tara'
                    && $this->record->status === Payment::STATUS_SUCCESS)
                ->action(function (TaraRefundService $refunds): void {
                    try {
                        $this->record = $refunds->refund($this->record);
                        $this->refreshRecord();
                        Notification::make()->title('مرجوعی تارا انجام شد')->success()->send();
                    } catch (\RuntimeException $e) {
                        Notification::make()->title('مرجوعی ناموفق')->body($e->getMessage())->danger()->send();
                    }
                }),
            Actions\Action::make('bajetRefund')
                ->label('استرداد باجت‌پی')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('استرداد پرداخت باجت‌پی')
                ->modalDescription('استرداد در درگاه جت‌پی ثبت می‌شود؛ وضعیت نهایی با استعلام refund-inquiry بررسی می‌شود.')
                ->visible(fn () => AdminAccess::canManageShopInAdmin()
                    && $this->record->gateway === 'bajet'
                    && $this->record->status === Payment::STATUS_SUCCESS)
                ->action(function (BajetRefundService $refunds): void {
                    try {
                        $this->record = $refunds->refund($this->record);
                        $this->refreshRecord();
                        Notification::make()->title('استرداد باجت‌پی انجام شد')->success()->send();
                    } catch (\RuntimeException $e) {
                        Notification::make()->title('استرداد ناموفق')->body($e->getMessage())->danger()->send();
                    }
                }),
            Actions\Action::make('digipayRefund')
                ->label('لغو دیجی‌پی')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('لغو / عودت پرداخت دیجی‌پی')
                ->modalDescription('درخواست عودت به API دیجی‌پی ارسال می‌شود (refunds).')
                ->visible(fn () => AdminAccess::canManageShopInAdmin()
                    && $this->record->gateway === 'digipay'
                    && $this->record->status === Payment::STATUS_SUCCESS
                    && ! isset((is_array($this->record->raw_response) ? $this->record->raw_response : [])['digipay_refund']))
                ->action(function (DigiPayRefundService $refunds): void {
                    try {
                        $this->record = $refunds->refund($this->record);
                        $this->refreshRecord();
                        Notification::make()->title('لغو دیجی‌پی ثبت شد')->success()->send();
                    } catch (\RuntimeException $e) {
                        Notification::make()->title('لغو دیجی‌پی ناموفق')->body($e->getMessage())->danger()->send();
                    }
                }),
            Actions\Action::make('paymentFileLog')
                ->label('لاگ فایل پرداخت')
                ->icon('heroicon-o-document-magnifying-glass')
                ->url(fn () => ViewPaymentLogs::getUrl().'?tracking='.urlencode($this->record->tracking_code))
                ->visible(fn (): bool => AdminAccess::canManageShopInAdmin()),
            Actions\Action::make('order')
                ->label('مشاهده سفارش')
                ->icon('heroicon-o-shopping-bag')
                ->url(fn () => OrderResource::getUrl('view', ['record' => $this->record->order_id]))
                ->visible(fn () => $this->record->order_id !== null),
            Actions\Action::make('back')
                ->label('لیست پرداخت‌ها')
                ->icon('heroicon-o-arrow-right')
                ->url(PaymentResource::getUrl('index'))
                ->color('gray'),
            PaymentDeleteActions::pageDeleteAction()
                ->visible(fn (): bool => AdminAccess::canManageShopInAdmin()),
        ];
    }

    public function getTitle(): string
    {
        return 'پرداخت #'.$this->record->tracking_code;
    }

    public function addNote(PaymentActivityLogger $logger): void
    {
        if (! AdminAccess::canManageShopInAdmin()) {
            return;
        }

        $this->validate(['newNote' => ['required', 'string', 'max:2000']]);

        $logger->byUser($this->record, auth()->user(), trim($this->newNote));
        $this->newNote = '';
        $this->refreshRecord();

        Notification::make()->title('یادداشت ثبت شد')->success()->send();
    }

    protected function refreshRecord(): void
    {
        $this->record = $this->record->fresh([
            'user',
            'paidByRepresentative',
            'order.user',
            'order.address',
            'order.items',
            'order.shippingMethod',
            'notes.author',
        ]);
    }
}
