<?php

namespace App\Filament\Representative\Resources\DraftOrderResource\Pages;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Order;
use App\Services\Representative\RepresentativeDraftOrderService;
use App\Services\Representative\RepresentativeProformaPaymentService;
use App\Support\ShopLabels;
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

        if ($order->canRepresentativePayProforma()) {
            $actions[] = Actions\Action::make('payProforma')
                ->label('پرداخت و رفتن به درگاه')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('پرداخت پیش‌فاکتور')
                ->modalDescription(fn (): string => 'پرداخت برای مشتری '
                    .($order->user?->name ?? '—')
                    .' از درگاه '
                    .ShopLabels::paymentMethod($order->payment_method)
                    .' انجام می‌شود.')
                ->action(function () use ($order): void {
                    $representative = auth()->user();
                    if ($representative === null) {
                        return;
                    }

                    try {
                        $url = app(RepresentativeProformaPaymentService::class)
                            ->initiateGatewayPayment($order->fresh(), $representative);
                        $this->redirect($url);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('خطا در اتصال به درگاه')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                });
        }

        return $actions;
    }

    public function payProformaFromPage(RepresentativeProformaPaymentService $payments): void
    {
        $order = $this->getRecord();
        $representative = auth()->user();

        if ($representative === null) {
            return;
        }

        try {
            $url = $payments->initiateGatewayPayment($order->fresh(), $representative);
            $this->redirect($url);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('خطا در اتصال به درگاه')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
