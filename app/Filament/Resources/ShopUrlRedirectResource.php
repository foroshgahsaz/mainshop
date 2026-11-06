<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShopUrlRedirectResource\Pages;
use App\Filament\Support\AdminTable;
use App\Models\ShopUrlRedirect;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;

class ShopUrlRedirectResource extends Resource
{
    protected static ?string $model = ShopUrlRedirect::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'ریدایرکت‌های URL';

    protected static ?string $modelLabel = 'ریدایرکت';

    protected static ?string $pluralModelLabel = 'ریدایرکت‌ها';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 25;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public static function canViewAny(): bool
    {
        return static::canAccess() && Schema::hasTable((new ShopUrlRedirect)->getTable());
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('مسیر و مقصد')
                ->description('مسیر قدیمی را بدون دامنه وارد کنید (مثلاً /محصول-قدیم). مقصد می‌تواند مسیر نسبی سایت یا آدرس کامل https باشد.')
                ->schema([
                    Forms\Components\TextInput::make('from_path')
                        ->label('از (مسیر قدیمی)')
                        ->required()
                        ->maxLength(191)
                        ->placeholder('/مسیر-قدیم')
                        ->unique(ignoreRecord: true)
                        ->rule(fn (): \Closure => function (string $attribute, $value, \Closure $fail): void {
                            $normalized = ShopUrlRedirect::normalizeFromPath((string) $value);
                            if ($normalized === '/') {
                                $fail('مسیر مبدأ نمی‌تواند فقط ریشهٔ سایت (/) باشد.');
                            }
                        })
                        ->live(onBlur: true)
                        ->afterStateHydrated(function (Forms\Components\Component $component, mixed $state): void {
                            if (! is_string($state) || $state === '') {
                                return;
                            }
                            $component->state(ShopUrlRedirect::normalizeFromPath($state));
                        })
                        ->dehydrateStateUsing(fn (?string $state): string => ShopUrlRedirect::normalizeFromPath((string) ($state ?? '')))
                        ->helperText('با / شروع شود؛ در صورت وارد کردن URL کامل، فقط مسیر ذخیره می‌شود.'),

                    Forms\Components\TextInput::make('to_target')
                        ->label('به (مقصد)')
                        ->required()
                        ->maxLength(1024)
                        ->placeholder('/products/slug یا https://…'),

                    Forms\Components\Select::make('status_code')
                        ->label('کد HTTP')
                        ->options([
                            301 => '۳۰۱ — دائمی',
                            302 => '۳۰۲ — موقت',
                            307 => '۳۰۷ — موقت (متد حفظ)',
                            308 => '۳۰۸ — دائمی (متد حفظ)',
                        ])
                        ->default(301)
                        ->required(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true),

                    Forms\Components\Textarea::make('notes')
                        ->label('یادداشت')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('from_path')->label('از')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('to_target')->label('به')->limit(40)->tooltip(fn ($state) => $state),
                Tables\Columns\TextColumn::make('status_code')->label('کد')->sortable()->alignCenter(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('به‌روزرسانی')->shopJalaliDate(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('حذف')->iconButton()
                    ->successNotificationTitle('حذف شد'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShopUrlRedirects::route('/'),
            'create' => Pages\CreateShopUrlRedirect::route('/create'),
            'edit' => Pages\EditShopUrlRedirect::route('/{record}/edit'),
        ];
    }
}
