<?php

namespace App\Filament\Representative\Widgets;

use App\Filament\Support\UserTableColumns;
use App\Filament\Representative\Pages\CreateDraftOrder;
use App\Filament\Representative\Resources\DraftOrderResource;
use App\Models\Order;
use App\Services\Representative\RepresentativeDashboardService;
use App\Support\ShopFormatter;
use App\Support\ShopLabels;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RepresentativeCurrentOrdersTable extends BaseWidget
{
    protected static bool $isLazy = false;

    protected static ?string $heading = 'سفارش‌های جاری';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function getExtraBodyAttributes(): array
    {
        return ['data-rep-tour' => 'rep-widget-current-orders'];
    }

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        $representativeId = (int) auth()->id();

        return $table
            ->query(
                app(RepresentativeDashboardService::class)
                    ->currentOrdersQuery($representativeId)
                    ->with(['user'])
                    ->withCount('items')
                    ->latest('updated_at')
            )
            ->columns([
                Tables\Columns\TextColumn::make('tracking_code')
                    ->label('کد')
                    ->searchable(),
                UserTableColumns::nameWithPhone(label: 'مشتری'),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Order::STATUS_DRAFT => 'در حال تکمیل',
                        Order::STATUS_PROFORMA => 'پیش‌فاکتور',
                        Order::STATUS_PENDING => 'در انتظار',
                        Order::STATUS_PROCESSING => 'در حال پردازش',
                        Order::STATUS_WAREHOUSE_PROCESSING => 'پردازش انبار',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('درگاه')
                    ->formatStateUsing(fn (?string $state): string => ShopLabels::paymentMethod($state))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('final_amount')
                    ->label('مبلغ')
                    ->formatStateUsing(fn (int $state): string => ShopFormatter::money($state)),
                Tables\Columns\TextColumn::make('stock_reserved_until')
                    ->label('مهلت رزرو')
                    ->shopJalaliDateTime()
                    ->placeholder('—')
                    ->color(fn (Order $record): string => $record->isReservationExpired() ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('آخرین تغییر')
                    ->shopJalaliDateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('continue')
                    ->label('ادامه')
                    ->icon('heroicon-o-pencil-square')
                    ->visible(fn (Order $record): bool => $record->isDraft())
                    ->url(fn (Order $record): string => CreateDraftOrder::getUrl(panel: 'representative').'?order='.$record->id),
                Tables\Actions\Action::make('view')
                    ->label('مشاهده')
                    ->icon('heroicon-m-eye')
                    ->visible(fn (Order $record): bool => $record->isProforma())
                    ->url(fn (Order $record): string => DraftOrderResource::getUrl('view', ['record' => $record], panel: 'representative')),
            ])
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25]);
    }
}
