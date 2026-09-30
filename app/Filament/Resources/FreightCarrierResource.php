<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FreightCarrierResource\Pages;
use App\Models\City;
use App\Models\FreightCarrier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FreightCarrierResource extends Resource
{
    protected static ?string $model = FreightCarrier::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'باربری‌ها (نمایندگی)';

    protected static ?string $modelLabel = 'باربری';

    protected static ?string $pluralModelLabel = 'باربری‌ها';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات باربری')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('نام باربری')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('carrier_number')
                        ->label('شماره باربری')
                        ->required()
                        ->maxLength(64)
                        ->helperText('کد یا شماره ثبت باربری'),
                    Forms\Components\Select::make('province_id')
                        ->label('استان')
                        ->relationship('province', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\Select::make('city_id')
                        ->label('شهر')
                        ->options(fn (Get $get) => City::query()
                            ->when($get('province_id'), fn ($q, $id) => $q->where('province_id', $id))
                            ->orderBy('position')
                            ->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->disabled(fn (Get $get): bool => ! $get('province_id')),
                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable(),
                Tables\Columns\TextColumn::make('carrier_number')->label('شماره باربری')->searchable(),
                Tables\Columns\TextColumn::make('province.name')->label('استان'),
                Tables\Columns\TextColumn::make('city.name')->label('شهر'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('به‌روزرسانی')->since(),
            ])
            ->defaultSort('name')
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFreightCarriers::route('/'),
            'create' => Pages\CreateFreightCarrier::route('/create'),
            'edit' => Pages\EditFreightCarrier::route('/{record}/edit'),
        ];
    }
}
