<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\PaymentResource;
use App\Models\PaymentNote;
use App\Services\Payment\PaymentActivityLogger;
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
        ];
    }

    public function getTitle(): string
    {
        return 'پرداخت #'.$this->record->tracking_code;
    }

    public function addNote(PaymentActivityLogger $logger): void
    {
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
            'order.user',
            'order.address',
            'order.items',
            'order.shippingMethod',
            'notes.author',
        ]);
    }
}
