<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use App\Filament\Resources\CouponResource\Concerns\SyncsCouponScopeRelations;
use App\Filament\Resources\Pages\CreateRecord;

class CreateCoupon extends CreateRecord
{
    use SyncsCouponScopeRelations;

    protected static string $resource = CouponResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractScopeFields($data);
    }

    protected function afterCreate(): void
    {
        $this->syncCouponScopeRelations($this->getRecord());
    }
}
