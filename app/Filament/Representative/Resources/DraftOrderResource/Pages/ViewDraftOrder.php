<?php

namespace App\Filament\Representative\Resources\DraftOrderResource\Pages;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Order;
use App\Services\Representative\ProformaReservationRequestService;
use App\Services\Representative\RepresentativeDraftOrderService;
use App\Services\Representative\RepresentativeProformaPaymentService;
use App\Support\ShopLabels;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class ViewDraftOrder extends ViewRecord
{
    public string $cashPayAmount = '';

    public ?int $plannedCreditEdit = null;

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

        $this->plannedCreditEdit = $order->proforma_planned_credit_amount !== null
            ? (int) $order->proforma_planned_credit_amount
            : null;
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
                        $this->redirect($url, navigate: false);
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

    public function requestReReservation(ProformaReservationRequestService $requests): void
    {
        $representative = auth()->user();

        if ($representative === null) {
            return;
        }

        try {
            $requests->request($this->getRecord()->fresh(), $representative);

            Notification::make()
                ->title('درخواست رزرو مجدد ثبت شد')
                ->body('درخواست برای مدیر منطقه ارسال شد. پس از تأیید، امکان پرداخت فعال می‌شود.')
                ->success()
                ->send();
        } catch (ValidationException $e) {
            Notification::make()
                ->title('ثبت درخواست ممکن نیست')
                ->body(collect($e->errors())->flatten()->first())
                ->danger()
                ->send();
        }

        $this->record = $this->getRecord()->fresh();
    }

    public function payProformaFromPage(RepresentativeProformaPaymentService $payments): void
    {
        $order = $this->getRecord();
        $representative = auth()->user();

        if ($representative === null) {
            return;
        }

        try {
            $url = $payments->initiateGatewayPayment($order->fresh(['payments']), $representative);
            $this->redirect($url, navigate: false);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('خطا در اتصال به درگاه')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function payCashPortion(RepresentativeProformaPaymentService $payments): void
    {
        $representative = auth()->user();

        if ($representative === null) {
            return;
        }

        $order = $this->getRecord()->fresh(['payments']);
        $amount = (int) preg_replace('/\D/', '', $this->cashPayAmount);

        if ($amount <= 0) {
            Notification::make()->title('مبلغ نقدی را وارد کنید')->danger()->send();

            return;
        }

        try {
            $url = $payments->initiateCashPayment($order, $representative, $amount);
            $this->redirect($url, navigate: false);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('خطا در اتصال به درگاه نقدی')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function payCreditPortion(RepresentativeProformaPaymentService $payments): void
    {
        $representative = auth()->user();

        if ($representative === null) {
            return;
        }

        $order = $this->getRecord()->fresh(['payments']);
        $split = app(\App\Services\Representative\RepresentativeProformaSplitPayment::class);
        $amount = $split->remainingCreditAmount($order);

        if ($amount <= 0) {
            Notification::make()->title('سهم اعتباری تسویه شده است')->warning()->send();

            return;
        }

        try {
            $url = $payments->initiateCreditPayment($order, $representative, $amount);
            $this->redirect($url, navigate: false);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('خطا در اتصال به درگاه اعتباری')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function saveSplitPaymentPlan(RepresentativeDraftOrderService $drafts): void
    {
        $order = $this->getRecord()->fresh(['payments']);

        if ($order->payments()->where('status', \App\Models\Payment::STATUS_SUCCESS)->exists()) {
            Notification::make()
                ->title('تغییر طرح پرداخت ممکن نیست')
                ->body('پس از اولین پرداخت موفق، طرح اعتبار/نقد قابل ویرایش نیست.')
                ->warning()
                ->send();

            return;
        }

        $credit = $this->plannedCreditEdit;
        $final = (int) $order->final_amount;

        try {
            \App\Support\OrderProformaSplitColumns::ensureAvailable();

            if ($credit !== null && $credit > 0 && $credit < $final) {
                app(\App\Services\Representative\RepresentativeProformaSplitPayment::class)
                    ->assertPlanAmounts($final, $credit);
                $order->update([
                    'proforma_planned_credit_amount' => $credit,
                    'proforma_cash_gateway' => $order->proforma_cash_gateway
                        ?: app(\App\Services\Representative\RepresentativeProformaSplitPayment::class)->cashGateway($order),
                ]);
            } else {
                $order->update([
                    'proforma_planned_credit_amount' => null,
                    'proforma_cash_gateway' => null,
                ]);
            }

            Notification::make()->title('طرح پرداخت ذخیره شد')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('خطا')->body($e->getMessage())->danger()->send();
        }

        $this->record = $order->fresh(['payments', 'user', 'freightCarrier']);
    }
}
