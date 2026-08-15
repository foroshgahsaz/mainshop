<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'پرداخت‌ها';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')->label('پیگیری')->searchable(),
                Tables\Columns\TextColumn::make('order.tracking_code')->label('سفارش'),
                Tables\Columns\TextColumn::make('user.name')->label('کاربر'),
                Tables\Columns\TextColumn::make('amount')->label('مبلغ')->numeric(),
                Tables\Columns\TextColumn::make('gateway')->label('درگاه'),
                Tables\Columns\TextColumn::make('status')->label('وضعیت')->badge(),
                Tables\Columns\TextColumn::make('paid_at')->label('تاریخ پرداخت')->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('جزئیات'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }
}
