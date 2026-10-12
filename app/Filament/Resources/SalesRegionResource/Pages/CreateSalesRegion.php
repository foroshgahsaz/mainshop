<?php

namespace App\Filament\Resources\SalesRegionResource\Pages;

use App\Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\SalesRegionResource;
use App\Models\SalesRegion;
use App\Services\Representative\SalesRegionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateSalesRegion extends CreateRecord
{
    protected static string $resource = SalesRegionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(SalesRegionService::class)->save(
                new SalesRegion,
                ['name' => $data['name'], 'sales_manager_id' => $data['sales_manager_id']],
                $data['province_ids'] ?? [],
            );
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(
                collect($e->errors())->mapWithKeys(fn (array $messages, string $key) => ['data.'.$key => $messages])->all()
            );
        }
    }
}
