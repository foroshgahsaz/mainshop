<?php

namespace App\Filament\Resources\CreditPlanResource\Pages;

use App\Filament\Resources\CreditPlanResource;
use App\Filament\Resources\Pages\CreateRecord;
use App\Services\Shop\CreditPlanGatewaySchemaRepair;

class CreateCreditPlan extends CreateRecord
{
    protected static string $resource = CreditPlanResource::class;

    public function mount(int|string|null $record = null): void
    {
        app(CreditPlanGatewaySchemaRepair::class)->ensureOnce();

        parent::mount($record);
    }
}
