<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingMethodResource\Pages;
use App\Models\ShippingMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'روش‌های ارسال';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->label('نام')->required(),
            Forms\Components\Textarea::make('description')->label('توضیحات'),
            Forms\Components\TextInput::make('price')->label('هزینه')->numeric()->required(),
            Forms\Components\TextInput::make('free_shipping_threshold')->label('آستانه ارسال رایگان')->numeric(),
            Forms\Components\TextInput::make('estimated_days')->label('زمان تحویل (روز)')->numeric(),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('نام'),
            Tables\Columns\TextColumn::make('price')->label('هزینه')->numeric(),
            Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShippingMethods::route('/'),
            'create' => Pages\CreateShippingMethod::route('/create'),
            'edit' => Pages\EditShippingMethod::route('/{record}/edit'),
        ];
    }
}
