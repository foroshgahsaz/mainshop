<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CreditPlanResource\Pages;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\AdminTable;
use App\Filament\Support\ShopMediaPicker;
use App\Models\CreditPlan;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CreditPlanResource extends Resource
{
    protected static ?string $model = CreditPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'طرح‌ها';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'طرح';

    protected static ?string $pluralModelLabel = 'طرح‌ها';

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات طرح')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('نام طرح')
                        ->required()
                        ->maxLength(255),
                    ShopMediaPicker::image('icon', 'credit-plans', 'آیکون'),
                    Forms\Components\TextInput::make('position')
                        ->label('ترتیب نمایش')
                        ->numeric()
                        ->default(0)
                        ->helperText('عدد کوچک‌تر = بالاتر در لیست و ویجت صفحه اصلی.'),
                    Forms\Components\Textarea::make('short_description')
                        ->label('توضیح کوتاه')
                        ->rows(3)
                        ->columnSpanFull()
                        ->helperText('در لیست «همه طرح‌ها» و کارت صفحه اصلی نمایش داده می‌شود.'),
                    Forms\Components\TextInput::make('plan_url')
                        ->label('لینک صفحه طرح')
                        ->required()
                        ->maxLength(2048)
                        ->helperText('آدرس داخلی (مثل /plans/digipay-leasing/) یا URL کامل.'),
                    Forms\Components\Toggle::make('show_on_homepage_desktop')
                        ->label('نمایش در صفحه اصلی (دسکتاپ)')
                        ->helperText('حداکثر ۴ طرح با این تیک در ردیف زیر بنر دسکتاپ نمایش داده می‌شود.'),
                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                AdminImageColumn::make('icon', 40, 'آیکون')->circular(),
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('show_on_homepage_desktop')->label('صفحه اصلی')->boolean(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('position')->label('ترتیب')->sortable()->alignCenter(),
                Tables\Columns\TextColumn::make('plan_url')->label('لینک')->limit(40)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('position')
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('حذف')->iconButton()
                    ->successNotificationTitle('حذف شد'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCreditPlans::route('/'),
            'create' => Pages\CreateCreditPlan::route('/create'),
            'edit' => Pages\EditCreditPlan::route('/{record}/edit'),
        ];
    }
}
