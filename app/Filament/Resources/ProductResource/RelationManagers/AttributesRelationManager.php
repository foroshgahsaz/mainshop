<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributes';

    protected static ?string $title = 'ویژگی‌های محصول';

    protected static ?string $modelLabel = 'ویژگی';

    protected static ?string $pluralModelLabel = 'ویژگی‌ها';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('ویژگی'),
            Tables\Columns\TextColumn::make('type')
                ->label('نوع')
                ->formatStateUsing(fn (string $state) => match ($state) {
                    'color' => 'رنگ',
                    'select' => 'انتخابی',
                    default => 'متن',
                }),
            Tables\Columns\TextColumn::make('values.value')
                ->label('مقادیر')
                ->badge()
                ->limitList(5),
            Tables\Columns\IconColumn::make('pivot.is_variation')->label('واریانت')->boolean(),
            Tables\Columns\IconColumn::make('pivot.is_required')->label('اجباری')->boolean(),
        ])->headerActions([
            Tables\Actions\AttachAction::make()
                ->label('افزودن ویژگی')
                ->preloadRecordSelect()
                ->recordSelectSearchColumns(['name'])
                ->form(fn (Tables\Actions\AttachAction $action): array => [
                    $action->getRecordSelect()->label('ویژگی'),
                    Forms\Components\Toggle::make('is_variation')
                        ->label('استفاده برای واریانت')
                        ->default(true)
                        ->helperText('برای ساخت ترکیب رنگ/سایز و...'),
                    Forms\Components\Toggle::make('is_required')->label('اجباری')->default(false),
                    Forms\Components\TextInput::make('position')->label('ترتیب')->numeric()->default(0),
                ]),
        ])->actions([
            Tables\Actions\EditAction::make()
                ->label('ویرایش')
                ->form([
                    Forms\Components\Toggle::make('is_variation')->label('استفاده برای واریانت'),
                    Forms\Components\Toggle::make('is_required')->label('اجباری'),
                    Forms\Components\TextInput::make('position')->label('ترتیب')->numeric(),
                ]),
            Tables\Actions\DetachAction::make()->label('حذف'),
        ]);
    }
}
