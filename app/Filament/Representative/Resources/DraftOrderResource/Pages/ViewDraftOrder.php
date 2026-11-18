<?php

namespace App\Filament\Representative\Resources\DraftOrderResource\Pages;

use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Order;
use App\Rules\DarikMemberCode;
use App\Services\Representative\ProformaReservationRequestService;
use App\Services\Representative\RepresentativeDraftOrderService;
use App\Services\Representative\RepresentativeProformaPaymentService;
use App\Services\Representative\RepresentativeProformaSplitPayment;
use App\Support\RialAmountInput;
use App\Support\ShopLabels;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;

class ViewDraftOrder extends ViewRecord
{
    public string $cashPayAmount = '';

    public string $plannedCreditEditRial = '';

    public ?int $plannedCreditEdit = null;

    public bool $showDarikWalletModal = false;

    public string $darikMemberCodeDraft = '';

    /** @var 'credit'|'full'|'save'|null پس از تأیید مودال */
    public ?string $darikPayIntent = null;

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
        $this->plannedCreditEditRial = RialAmountInput::formatRialFromToman($this->plannedCreditEdit);

        $split = app(RepresentativeProformaSplitPayment::class);
        if ($order->canRepresentativePayProforma() && $split->usesSplitPlan($order)) {
            $remainCash = $split->remainingCashAmount($order);
            if ($remainCash > 0) {
                $this->cashPayAmount = RialAmountInput::formatRialFromToman($remainCash);
            }
        }

        if ($this->shouldAutoOpenDarikWalletModal($order)) {
            $this->openDarikWalletModal($order, 'credit');
        }
    }

    public function updatedPlannedCreditEditRial(string $value): void
    {
        $this->plannedCreditEditRial = RialAmountInput::sanitizeFormattedInput($value);
        $this->plannedCreditEdit = RialAmountInput::tomanFromInput($this->plannedCreditEditRial);
    }

    public function updatedCashPayAmount(string $value): void
    {
        $this->cashPayAmount = RialAmountInput::sanitizeFormattedInput($value);
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
                ->action(fn () => $this->payProformaFromPage(app(RepresentativeProformaPaymentService::class)));
        }

        return $actions;
    }

    public function openDarikWalletModal(Order $order, string $intent): void
    {
        $this->darikMemberCodeDraft = (string) ($order->user?->darik_member_code ?? '');
        $this->darikPayIntent = $intent;
        $this->showDarikWalletModal = true;
        $this->resetValidation(['darikMemberCodeDraft']);
    }

    public function cancelDarikWalletModal(): void
    {
        $this->showDarikWalletModal = false;
        $this->darikPayIntent = null;
        $this->resetValidation(['darikMemberCodeDraft']);
    }

    public function confirmDarikWalletModal(): void
    {
        $this->validate(
            [
                'darikMemberCodeDraft' => ['required', 'string', new DarikMemberCode],
            ],
            [
                'darikMemberCodeDraft.required' => 'شناسه کیف پول داریک را وارد کنید.',
            ]
        );

        $order = $this->getRecord()->fresh(['user']);
        $normalized = DarikMemberCode::normalize($this->darikMemberCodeDraft);

        $customer = $order->user;
        if ($customer && $customer->darik_member_code !== $normalized) {
            $customer->update(['darik_member_code' => $normalized]);
        }

        $intent = $this->darikPayIntent;
        $this->showDarikWalletModal = false;
        $this->darikPayIntent = null;
        $this->resetValidation(['darikMemberCodeDraft']);

        $payments = app(RepresentativeProformaPaymentService::class);

        try {
            if ($intent === 'credit') {
                $this->executePayCreditPortion($payments);
            } elseif ($intent === 'full') {
                $this->executePayProformaFromPage($payments);
            } else {
                Notification::make()
                    ->title('شناسه داریک ذخیره شد')
                    ->body('برای مشتری '.$customer?->name.' ثبت شد. اکنون می‌توانید پرداخت اعتبار را بزنید.')
                    ->success()
                    ->send();
            }
        } catch (\Throwable $e) {
            Notification::make()
                ->title('خطا در اتصال به درگاه')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }

        $this->record = $order->fresh(['payments', 'user', 'freightCarrier']);
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
                ->body('درخواست برای بنکدار ارسال شد. پس از تأیید، امکان پرداخت فعال می‌شود.')
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
        $order = $this->getRecord()->fresh(['payments', 'user']);

        if ($this->shouldCollectDarikWalletBeforeFullGatewayPay($order)) {
            $this->openDarikWalletModal($order, 'full');

            return;
        }

        $this->executePayProformaFromPage($payments);
    }

    public function payCashPortion(RepresentativeProformaPaymentService $payments): void
    {
        $representative = auth()->user();

        if ($representative === null) {
            return;
        }

        $order = $this->getRecord()->fresh(['payments']);
        $split = app(RepresentativeProformaSplitPayment::class);
        $amount = RialAmountInput::tomanFromInput($this->cashPayAmount) ?? 0;

        if ($amount <= 0) {
            $amount = $split->remainingCashAmount($order);
        }

        if ($amount <= 0) {
            Notification::make()->title('مانده نقدی برای پرداخت وجود ندارد')->warning()->send();

            return;
        }

        $remaining = $split->usesSplitPlan($order)
            ? $split->remainingCashAmount($order)
            : $order->remainingAmount();
        if ($amount > $remaining) {
            $amount = $remaining;
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
        $order = $this->getRecord()->fresh(['payments', 'user']);

        if ($this->shouldCollectDarikWalletBeforeCreditPay($order)) {
            $this->openDarikWalletModal($order, 'credit');

            return;
        }

        $this->executePayCreditPortion($payments);
    }

    protected function executePayProformaFromPage(RepresentativeProformaPaymentService $payments): void
    {
        $order = $this->getRecord();
        $representative = auth()->user();

        if ($representative === null) {
            return;
        }

        try {
            $url = $payments->initiateGatewayPayment($order->fresh(['payments', 'user']), $representative);
            $this->redirect($url, navigate: false);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('خطا در اتصال به درگاه')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function executePayCreditPortion(RepresentativeProformaPaymentService $payments): void
    {
        $representative = auth()->user();

        if ($representative === null) {
            return;
        }

        $order = $this->getRecord()->fresh(['payments', 'user']);
        $split = app(RepresentativeProformaSplitPayment::class);
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

    protected function customerHasValidDarikCode(Order $order): bool
    {
        $code = DarikMemberCode::normalize($order->user?->darik_member_code ?? '');

        return (bool) preg_match('/^5892\d{12}$/', $code);
    }

    protected function orderCreditGatewayIsDarik(Order $order): bool
    {
        return (string) $order->payment_method === 'darik';
    }

    protected function shouldCollectDarikWalletBeforeCreditPay(Order $order): bool
    {
        if (! $order->canRepresentativePayProforma()) {
            return false;
        }

        if (! $this->orderCreditGatewayIsDarik($order)) {
            return false;
        }

        return ! $this->customerHasValidDarikCode($order);
    }

    protected function shouldCollectDarikWalletBeforeFullGatewayPay(Order $order): bool
    {
        if (! $order->canRepresentativePayProforma()) {
            return false;
        }

        $split = app(RepresentativeProformaSplitPayment::class);

        if ($split->usesSplitPlan($order) && $split->remainingCashAmount($order) > 0) {
            return false;
        }

        if ($split->usesSplitPlan($order) && $split->remainingCreditAmount($order) <= 0) {
            return false;
        }

        return $this->shouldCollectDarikWalletBeforeCreditPay($order);
    }

    protected function shouldAutoOpenDarikWalletModal(Order $order): bool
    {
        if (! $this->shouldCollectDarikWalletBeforeCreditPay($order)) {
            return false;
        }

        $split = app(RepresentativeProformaSplitPayment::class);

        if ($split->usesSplitPlan($order) && $split->remainingCashAmount($order) > 0) {
            return false;
        }

        return $split->remainingCreditAmount($order) > 0 || ! $split->usesSplitPlan($order);
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

        $this->plannedCreditEdit = RialAmountInput::tomanFromInput($this->plannedCreditEditRial);
        $credit = $this->plannedCreditEdit;
        $final = (int) $order->final_amount;

        try {
            \App\Support\OrderProformaSplitColumns::ensureAvailable();

            if ($credit !== null && $credit > 0 && $credit < $final) {
                $split = app(RepresentativeProformaSplitPayment::class);
                $split->assertPlanAmounts($final, $credit);
                $plan = $order->creditPlan;
                $cashGw = $order->proforma_cash_gateway
                    ?: $plan?->cash_gateway
                    ?: $split->cashGateway($order);
                $payload = [
                    'proforma_planned_credit_amount' => $credit,
                    'proforma_cash_gateway' => $cashGw,
                ];
                if ($plan?->credit_gateway) {
                    $payload['payment_method'] = (string) $plan->credit_gateway;
                }
                $order->update($payload);
            } else {
                $payload = [
                    'proforma_planned_credit_amount' => null,
                    'proforma_cash_gateway' => null,
                ];
                if ($order->creditPlan?->cash_gateway && ($credit === null || $credit <= 0)) {
                    $payload['payment_method'] = (string) $order->creditPlan->cash_gateway;
                } elseif ($order->creditPlan?->credit_gateway && $credit !== null && $credit >= $final) {
                    $payload['payment_method'] = (string) $order->creditPlan->credit_gateway;
                }
                $order->update($payload);
            }

            Notification::make()->title('طرح پرداخت ذخیره شد')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('خطا')->body($e->getMessage())->danger()->send();
        }

        $this->record = $order->fresh(['payments', 'user', 'freightCarrier']);
    }
}