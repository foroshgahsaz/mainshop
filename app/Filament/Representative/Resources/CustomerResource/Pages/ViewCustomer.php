<?php

namespace App\Filament\Representative\Resources\CustomerResource\Pages;

use App\Filament\Representative\Resources\CustomerResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('مشتری')->schema([
                TextEntry::make('name')->label('نام'),
                TextEntry::make('phone')->label('موبایل'),
            ])->columns(2),
            Section::make('آدرس پیش‌فرض')
                ->schema([
                    TextEntry::make('default_address')
                        ->label('')
                        ->state(function ($record) {
                            $address = $record->addresses->firstWhere('is_default', true)
                                ?? $record->addresses->first();

                            if ($address === null) {
                                return '—';
                            }

                            return trim(implode(' — ', array_filter([
                                $address->province,
                                $address->city,
                                $address->address,
                                $address->postal_code,
                            ])));
                        }),
                ]),
        ]);
    }
}
