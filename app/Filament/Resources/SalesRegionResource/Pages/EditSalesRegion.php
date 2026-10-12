<?php

namespace App\Filament\Resources\SalesRegionResource\Pages;

use App\Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\SalesRegionResource;
use App\Models\SalesRegion;
use App\Services\Representative\SalesRegionService;
use Filament\Actions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditSalesRegion extends EditRecord
{
    protected static string $resource = SalesRegionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()->label('حذف'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var SalesRegion $region */
        $region = $this->getRecord();
        $data['province_ids'] = $region->provinces()->pluck('id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var SalesRegion $record */
        try {
            return app(SalesRegionService::class)->save(
                $record,
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
