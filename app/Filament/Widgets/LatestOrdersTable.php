<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestOrdersTable extends BaseWidget
{
    protected static ?string $heading = 'آخرین سفارشات';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with('user')
                    ->latest()
                    ->limit(8)
            )
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')
                    ->label('کد سفارش')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('مشتری')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('مبلغ')
                    ->numeric()
                    ->suffix(' تومان'),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Order::STATUS_PENDING => 'در انتظار',
                        Order::STATUS_PROCESSING => 'در حال پردازش',
                        Order::STATUS_SHIPPED => 'ارسال شده',
                        Order::STATUS_DELIVERED => 'تحویل شده',
                        Order::STATUS_CANCELED => 'لغو شده',
                        Order::STATUS_RETURNED => 'مرجوعی',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        Order::STATUS_DELIVERED => 'success',
                        Order::STATUS_PENDING => 'warning',
                        Order::STATUS_CANCELED, Order::STATUS_RETURNED => 'danger',
                        default => 'info',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('جزئیات')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}
