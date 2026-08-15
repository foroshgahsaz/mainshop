<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductQuestionResource\Pages;
use App\Models\ProductQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductQuestionResource extends Resource
{
    protected static ?string $model = ProductQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'پرسش و پاسخ';

    protected static ?string $modelLabel = 'پرسش';

    protected static ?string $pluralModelLabel = 'پرسش و پاسخ';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('product_label')
                ->label('محصول')
                ->content(fn (ProductQuestion $record): string => $record->product?->name ?? '—'),
            Forms\Components\Placeholder::make('user_label')
                ->label('کاربر')
                ->content(fn (ProductQuestion $record): string => $record->user?->name ?? '—'),
            Forms\Components\Textarea::make('question')
                ->label('پرسش')
                ->rows(3)
                ->disabled()
                ->columnSpanFull(),
            Forms\Components\Toggle::make('is_approved')
                ->label('نمایش در سایت'),
            Forms\Components\Textarea::make('answer')
                ->label('پاسخ فروشگاه')
                ->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
                    ->label('محصول')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('کاربر'),
                Tables\Columns\TextColumn::make('question')
                    ->label('پرسش')
                    ->limit(40)
                    ->wrap(),
                Tables\Columns\IconColumn::make('is_approved')
                    ->label('تایید')
                    ->boolean(),
                Tables\Columns\IconColumn::make('answer')
                    ->label('پاسخ')
                    ->boolean()
                    ->getStateUsing(fn (ProductQuestion $record): bool => filled($record->answer)),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('تایید شده'),
                Tables\Filters\Filter::make('unanswered')
                    ->label('بدون پاسخ')
                    ->query(fn ($query) => $query->whereNull('answer')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductQuestions::route('/'),
            'edit' => Pages\EditProductQuestion::route('/{record}/edit'),
        ];
    }
}
