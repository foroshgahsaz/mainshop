<?php

namespace App\Filament\Representative\Resources\CustomerResource\Pages;

use App\Filament\Representative\Resources\CustomerResource;
use App\Models\User;
use App\Services\Representative\RepresentativeCustomerService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateCustomer extends CreateRecord
{
    protected static string $layout = 'filament-panels::components.layout.representative';

    protected static string $resource = CustomerResource::class;

    public ?string $existingCustomerMessage = null;

    public ?int $existingCustomerId = null;

    public bool $canTransferExistingCustomer = false;

    public bool $transferOtpSent = false;

    public string $transferOtp = '';

    public function lookupNationalCode(?string $state): void
    {
        $this->resetTransferState();

        $code = trim((string) $state);
        if ($code === '' || strlen(preg_replace('/\D+/', '', $code) ?? '') < 10) {
            return;
        }

        /** @var User $representative */
        $representative = auth()->user();
        $info = app(RepresentativeCustomerService::class)->lookupByNationalCode($code, $representative);

        if ($info === null) {
            return;
        }

        $this->existingCustomerMessage = $info['message'];
        $this->existingCustomerId = (int) $info['customer']->id;
        $this->canTransferExistingCustomer = ! $info['already_mine'];
    }

    public function requestCustomerTransfer(): void
    {
        if (! $this->existingCustomerId) {
            return;
        }

        /** @var User $representative */
        $representative = auth()->user();
        $customer = User::query()->find($this->existingCustomerId);

        if ($customer === null) {
            Notification::make()->danger()->title('مشتری یافت نشد')->send();

            return;
        }

        try {
            app(RepresentativeCustomerService::class)->sendTransferOtp($representative, $customer);
            $this->transferOtpSent = true;
            $this->transferOtp = '';
            Notification::make()->success()->title('کد تأیید برای مشتری ارسال شد')->send();
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'ارسال کد ممکن نیست.';
            Notification::make()->danger()->title($message)->send();
        }
    }

    public function confirmCustomerTransfer(): void
    {
        if (! $this->existingCustomerId) {
            return;
        }

        /** @var User $representative */
        $representative = auth()->user();
        $customer = User::query()->find($this->existingCustomerId);

        if ($customer === null) {
            Notification::make()->danger()->title('مشتری یافت نشد')->send();

            return;
        }

        try {
            $transferred = app(RepresentativeCustomerService::class)
                ->confirmTransfer($representative, $customer, $this->transferOtp);

            Notification::make()
                ->success()
                ->title('مشتری به نمایندگی شما منتقل شد')
                ->send();

            $this->redirect(static::getResource()::getUrl('view', ['record' => $transferred]));
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?: 'انتقال ناموفق بود.';
            Notification::make()->danger()->title($message)->send();
        }
    }

    protected function resetTransferState(): void
    {
        $this->existingCustomerMessage = null;
        $this->existingCustomerId = null;
        $this->canTransferExistingCustomer = false;
        $this->transferOtpSent = false;
        $this->transferOtp = '';
    }

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $representative */
        $representative = auth()->user();

        $raw = $this->form->getRawState();

        return app(RepresentativeCustomerService::class)->create($representative, [
            'name' => (string) ($raw['name'] ?? ''),
            'phone' => (string) ($raw['phone'] ?? ''),
            'national_code' => (string) ($raw['national_code'] ?? ''),
            'province_id' => (int) ($raw['province_id'] ?? 0),
            'city_id' => (int) ($raw['city_id'] ?? 0),
            'address' => (string) ($raw['address'] ?? ''),
            'postal_code' => $raw['postal_code'] ?? null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
