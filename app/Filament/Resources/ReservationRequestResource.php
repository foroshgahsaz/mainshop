<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReservationRequestResource\Pages;
use App\Filament\Support\AdminTable;
use App\Models\ProformaReservationRequest;
use App\Models\User;
use App\Services\Representative\ProformaReservationRequestService;
use App\Support\AdminAccess;
use App\Support\ShopLabels;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class ReservationRequestResource extends Resource
{
    protected static ?string $model = ProformaReservationRequest::class;

    protected static ?string $slug = 'reservation-requests';

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'درخواست‌ها';

    protected static ?string $modelLabel = 'درخواست رزرو مجدد';

    protected static ?string $pluralModelLabel = 'درخواست‌های رزرو مجدد';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'order:id,tracking_code,user_id,final_amount,stock_reserved,stock_reserved_until',
            'order.user:id,name,phone',
            'representative:id,name,phone',
            'salesManager:id,name',
            'handledBy:id,name',
        ]);

        $user = auth()->user();

        return $user instanceof User ? $query->visibleTo($user) : $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('order.tracking_code')
                    ->label('پیش‌فاکتور')
                    ->searchable()
                    ->url(fn (ProformaReservationRequest $record): ?string => $record->order
                        ? OrderResource::getUrl('view', ['record' => $record->order])
                        : null),
                Tables\Columns\TextColumn::make('representative.name')
                    ->label('نماینده')
                    ->description(fn (ProformaReservationRequest $record): ?string => $record->representative?->phone)
                    ->searchable(),
                Tables\Columns\TextColumn::make('order.user.name')
                    ->label('مشتری')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('order.final_amount')
                    ->label('مبلغ')
                    ->formatStateUsing(fn (?int $state) => ShopLabels::formatMoney($state)),
                Tables\Columns\TextColumn::make('salesManager.name')
                    ->label('مدیر منطقه')
                    ->placeholder('همه مدیران')
                    ->visible(fn (): bool => AdminAccess::isFullAdmin()),
                Tables\Columns\TextColumn::make('status')
                    ->label('وضعیت')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ProformaReservationRequest::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        ProformaReservationRequest::STATUS_APPROVED => 'success',
                        ProformaReservationRequest::STATUS_REJECTED => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('response_note')
                    ->label('پاسخ')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ درخواست')
                    ->shopJalaliDateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('handled_at')
                    ->label('تاریخ بررسی')
                    ->shopJalaliDateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('وضعیت')
                    ->options(ProformaReservationRequest::statusLabels()),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('رزرو مجدد')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (ProformaReservationRequest $record): bool => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('رزرو مجدد موجودی')
                    ->modalDescription('موجودی اقلام پیش‌فاکتور دوباره رزرو می‌شود و نماینده می‌تواند پرداخت را انجام دهد.')
                    ->form([
                        Forms\Components\Textarea::make('response_note')->label('توضیح (اختیاری)')->rows(2),
                    ])
                    ->action(fn (ProformaReservationRequest $record, array $data) => static::handle($record, 'approve', $data['response_note'] ?? null)),
                Tables\Actions\Action::make('reject')
                    ->label('رد')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (ProformaReservationRequest $record): bool => $record->isPending())
                    ->modalHeading('رد درخواست رزرو مجدد')
                    ->form([
                        Forms\Components\Textarea::make('response_note')->label('دلیل رد')->rows(2),
                    ])
                    ->action(fn (ProformaReservationRequest $record, array $data) => static::handle($record, 'reject', $data['response_note'] ?? null)),
            ])
            ->bulkActions([]);
    }

    protected static function handle(ProformaReservationRequest $record, string $decision, ?string $note): void
    {
        $actor = auth()->user();

        if (! $actor instanceof User) {
            return;
        }

        $service = app(ProformaReservationRequestService::class);

        try {
            $decision === 'approve'
                ? $service->approve($record, $actor, $note)
                : $service->reject($record, $actor, $note);

            Notification::make()
                ->title($decision === 'approve' ? 'موجودی دوباره رزرو شد' : 'درخواست رد شد')
                ->success()
                ->send();
        } catch (ValidationException $e) {
            Notification::make()
                ->title('انجام نشد')
                ->body(collect($e->errors())->flatten()->first())
                ->danger()
                ->send();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('موجودی کافی نیست')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservationRequests::route('/'),
        ];
    }
}
