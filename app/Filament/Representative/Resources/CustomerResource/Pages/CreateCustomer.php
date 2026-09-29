<?php

namespace App\Filament\Representative\Resources\CustomerResource\Pages;

use App\Filament\Representative\Resources\CustomerResource;
use App\Models\User;
use App\Services\Representative\RepresentativeCustomerService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateCustomer extends CreateRecord
{
    protected static string $layout = 'filament-panels::components.layout.representative';

    protected static string $resource = CustomerResource::class;

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
