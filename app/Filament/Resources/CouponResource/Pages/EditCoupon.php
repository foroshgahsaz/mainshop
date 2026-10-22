<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Resources\CouponResource;
use Filament\Actions;
use App\Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    protected static string $resource = CouponResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analytics')
                ->label('آنالیز')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => CouponResource::getUrl('analytics', ['record' => $this->getRecord()])),
            Actions\DeleteAction::make(),
        ];
    }
}
