<?php

namespace App\Filament\Resources\CreditPlanResource\Pages;

use App\Filament\Resources\CreditPlanResource;
use App\Filament\Resources\Pages\EditRecord;
use App\Services\Shop\CreditPlanGatewaySchemaRepair;

class EditCreditPlan extends EditRecord
{
    protected static string $resource = CreditPlanResource::class;

    public function mount(int|string $record): void
    {
        app(CreditPlanGatewaySchemaRepair::class)->ensureOnce();

        parent::mount($record);
    }
}
