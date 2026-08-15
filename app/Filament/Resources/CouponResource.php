<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'کدهای تخفیف';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')->label('کد')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('type')->label('نوع')->options(['percent' => 'درصدی', 'fixed' => 'مبلغ ثابت'])->required(),
            Forms\Components\TextInput::make('value')->label('مقدار')->numeric()->required(),
            Forms\Components\TextInput::make('max_discount')->label('سقف تخفیف')->numeric(),
            Forms\Components\TextInput::make('usage_limit')->label('محدودیت کل')->numeric(),
            Forms\Components\TextInput::make('usage_per_user')->label('محدودیت هر کاربر')->numeric()->default(1),
            Forms\Components\TextInput::make('minimum_order_amount')->label('حداقل سفارش')->numeric(),
            Forms\Components\DateTimePicker::make('starts_at')->label('شروع'),
            Forms\Components\DateTimePicker::make('expires_at')->label('پایان'),
            Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
            Forms\Components\TextInput::make('description')->label('توضیح'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->label('کد')->searchable(),
            Tables\Columns\TextColumn::make('type')->label('نوع'),
            Tables\Columns\TextColumn::make('value')->label('مقدار'),
            Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
