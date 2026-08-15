<?php

namespace App\Filament\Resources\AttributeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ValuesRelationManager extends RelationManager
{
    protected static string $relationship = 'values';

    protected static ?string $title = 'مقادیر ویژگی';

    protected static ?string $modelLabel = 'مقدار';

    protected static ?string $pluralModelLabel = 'مقادیر';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('value')
                ->label('مقدار')
                ->required()
                ->maxLength(255)
                ->placeholder('مثلاً: قرمز، L، 128GB'),
            Forms\Components\ColorPicker::make('color_code')
                ->label('کد رنگ')
                ->visible(fn () => $this->getOwnerRecord()->type === 'color'),
            Forms\Components\TextInput::make('position')
                ->label('ترتیب')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('is_active')
                ->label('فعال')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('value')->label('مقدار'),
            Tables\Columns\ColorColumn::make('color_code')->label('رنگ'),
            Tables\Columns\TextColumn::make('position')->label('ترتیب'),
            Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
        ])->headerActions([
            Tables\Actions\CreateAction::make()->label('افزودن مقدار'),
        ])->actions([
            Tables\Actions\EditAction::make()->label('ویرایش'),
            Tables\Actions\DeleteAction::make()->label('حذف'),
        ]);
    }
}
