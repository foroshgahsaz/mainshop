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

        $state = $this->form->getState();

        return app(RepresentativeCustomerService::class)->create($representative, [
            'name' => (string) ($state['name'] ?? ''),
            'phone' => (string) ($state['phone'] ?? ''),
            'national_code' => (string) ($state['national_code'] ?? ''),
            'province_id' => (int) ($state['province_id'] ?? 0),
            'city_id' => (int) ($state['city_id'] ?? 0),
            'address' => (string) ($state['address'] ?? ''),
            'postal_code' => $state['postal_code'] ?? null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
