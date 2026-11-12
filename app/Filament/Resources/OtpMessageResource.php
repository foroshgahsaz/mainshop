<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OtpMessageResource\Pages;
use App\Filament\Support\AdminTable;
use App\Models\OtpMessage;
use App\Support\AdminAccess;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OtpMessageResource extends Resource
{
    protected static ?string $model = OtpMessage::class;

    protected static ?string $slug = 'otp-messages';

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'مدیریت OTP';

    protected static ?string $modelLabel = 'OTP';

    protected static ?string $pluralModelLabel = 'OTPهای ارسال‌شده';

    protected static ?string $navigationGroup = 'کاربران';

    protected static ?int $navigationSort = 8;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name,phone');
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->defaultSort('sent_at', 'desc')
            ->emptyStateHeading('هنوز OTP ارسال نشده')
            ->emptyStateDescription('با هر ارسال کد ورود (فروشگاه یا ادمین)، ردیف اینجا ثبت می‌شود.')
            ->searchPlaceholder('جستجوی شماره، کد یا نام')
            ->columns([
                Tables\Columns\TextColumn::make('phone')
                    ->label('شماره موبایل')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr'])
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('code')
                    ->label('کد OTP')
                    ->searchable()
                    ->copyable()
                    ->extraCellAttributes(['dir' => 'ltr'])
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('نام و نام خانوادگی')
                    ->getStateUsing(fn (OtpMessage $record): string => $record->displayName())
                    ->searchable()
                    ->placeholder('—')
                    ->url(fn (OtpMessage $record): ?string => $record->user_id
                        ? UserResource::getUrl('edit', ['record' => $record->user_id])
                        : null)
                    ->color(fn (OtpMessage $record): ?string => $record->user_id ? 'primary' : null),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('زمان ارسال')
                    ->shopJalaliDateTime('Y/m/d H:i:s')
                    ->sortable(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOtpMessages::route('/'),
        ];
    }
}
