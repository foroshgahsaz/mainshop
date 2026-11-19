<?php

namespace App\Filament\Resources\CouponResource\Pages;

use App\Filament\Pages\CouponAnalyticsPage;
use App\Filament\Resources\CouponResource;
use App\Filament\Resources\CouponResource\Concerns\SyncsCouponScopeRelations;
use Filament\Actions;
use App\Filament\Resources\Pages\EditRecord;

class EditCoupon extends EditRecord
{
    use SyncsCouponScopeRelations;

    protected static string $resource = CouponResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $data['product_ids'] = $record->products()->pluck('products.id')->all();
        $data['category_ids'] = $record->categories()->pluck('categories.id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractScopeFields($data);
    }

    protected function afterSave(): void
    {
        $this->syncCouponScopeRelations($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analytics')
                ->label('آنالیز')
                ->icon('heroicon-o-chart-bar')
                ->url(fn (): string => CouponAnalyticsPage::getUrl(['record' => $this->getRecord()->getKey()])),
            Actions\DeleteAction::make(),
        ];
    }
}
