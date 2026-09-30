<?php

namespace App\Filament\Representative\Resources\DraftOrderResource\Pages;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Order;
use App\Services\Representative\RepresentativeDraftOrderService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewDraftOrder extends ViewRecord
{
    protected static string $resource = DraftOrderResource::class;

    protected static string $layout = 'filament-panels::components.layout.representative';

    protected static string $view = 'filament.representative.pages.view-draft-order';

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $order = $this->getRecord();

        if (! $order instanceof Order) {
            return;
        }

        app(RepresentativeDraftOrderService::class)->assertRepCanView($order, auth()->user());
    }

    public function getTitle(): string|Htmlable
    {
        $order = $this->getRecord();

        return 'پیش‌فاکتور '.$order->tracking_code;
    }

    protected function getHeaderActions(): array
    {
        $order = $this->getRecord();

        $actions = [
            Actions\Action::make('downloadPdf')
                ->label('دانلود PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('representative.proforma.pdf', $order))
                ->openUrlInNewTab(),
        ];

        if ($order->isProforma() && $order->remainingAmount() > 0) {
            $payUrl = route('account.orders.show', $order);
            $actions[] = Actions\Action::make('copyCustomerPayLink')
                ->label('کپی لینک پرداخت مشتری')
                ->icon('heroicon-o-link')
                ->color('success')
                ->action(function () use ($payUrl): void {
                    $this->js('navigator.clipboard.writeText('.json_encode($payUrl).')');
                    Notification::make()
                        ->title('لینک پرداخت مشتری کپی شد')
                        ->body('مشتری باید با حساب خودش در فروشگاه وارد شود — با حساب نماینده این صفحه باز نمی‌شود.')
                        ->success()
                        ->send();
                });
        }

        return $actions;
    }
}
