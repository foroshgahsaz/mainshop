<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Filament\Support\AdminTable;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Route as RouteFacade;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-bars-3';

    protected static ?string $navigationLabel = 'منوی سایت';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'آیتم منو';

    protected static ?string $pluralModelLabel = 'منوی سایت';

    public static function form(Form $form): Form
    {
        $routes = collect(RouteFacade::getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods(), true) && $route->getName())
            ->mapWithKeys(fn ($route) => [$route->getName() => $route->getName()])
            ->sortKeys();

        return $form->schema([
            Forms\Components\Section::make('اطلاعات منو')->schema([
                Forms\Components\TextInput::make('label')
                    ->label('عنوان')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('item_type')
                    ->label('نوع آیتم')
                    ->options([
                        MenuItem::TYPE_LINK => 'لینک معمولی',
                        MenuItem::TYPE_MEGA_TRIGGER => 'مگامنو (دسکتاپ)',
                        MenuItem::TYPE_MEGA_PROMO => 'باکس تبلیغ مگامنو',
                        MenuItem::TYPE_ACCORDION => 'آکاردئون موبایل',
                    ])
                    ->default(MenuItem::TYPE_LINK)
                    ->required()
                    ->live(),
                Forms\Components\Select::make('location')
                    ->label('نمایش در')
                    ->options([
                        MenuItem::LOCATION_BOTH => 'دسکتاپ و موبایل',
                        MenuItem::LOCATION_DESKTOP => 'فقط دسکتاپ',
                        MenuItem::LOCATION_MOBILE => 'فقط موبایل',
                    ])
                    ->default(MenuItem::LOCATION_BOTH)
                    ->required(),
                Forms\Components\Select::make('link_type')
                    ->label('نوع لینک')
                    ->options([
                        'route' => 'مسیر داخلی (Route)',
                        'url' => 'آدرس خارجی',
                        'category' => 'دسته‌بندی',
                        'page' => 'صفحه ثابت',
                    ])
                    ->default('route')
                    ->live()
                    ->visible(fn (Get $get) => ! in_array($get('item_type'), [MenuItem::TYPE_MEGA_TRIGGER], true)),
                Forms\Components\Select::make('link_value')
                    ->label('مسیر')
                    ->options($routes)
                    ->searchable()
                    ->dehydrated(fn (Get $get) => $get('link_type') === 'route')
                    ->visible(fn (Get $get) => $get('link_type') === 'route'),
                Forms\Components\TextInput::make('link_value')
                    ->label('آدرس')
                    ->url()
                    ->dehydrated(fn (Get $get) => $get('link_type') === 'url')
                    ->visible(fn (Get $get) => $get('link_type') === 'url'),
                Forms\Components\Select::make('link_value')
                    ->label('دسته‌بندی')
                    ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->dehydrated(fn (Get $get) => $get('link_type') === 'category')
                    ->visible(fn (Get $get) => $get('link_type') === 'category'),
                Forms\Components\Select::make('link_value')
                    ->label('صفحه')
                    ->options(fn () => Page::query()->orderBy('title')->pluck('title', 'slug'))
                    ->searchable()
                    ->dehydrated(fn (Get $get) => $get('link_type') === 'page')
                    ->visible(fn (Get $get) => $get('link_type') === 'page'),
                Forms\Components\TextInput::make('mega_column')
                    ->label('ستون مگامنو (۱ تا ۴)')
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(4)
                    ->visible(fn (Get $get) => $get('item_type') === MenuItem::TYPE_MEGA_PROMO),
                Forms\Components\TextInput::make('position')
                    ->label('ترتیب')
                    ->numeric()
                    ->default(0)
                    ->minValue(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('فعال')
                    ->default(true),
                Forms\Components\Toggle::make('open_in_new_tab')
                    ->label('باز شدن در تب جدید')
                    ->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('label')->label('عنوان')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('item_type')->label('نوع')->badge(),
                Tables\Columns\TextColumn::make('location')->label('محل')->badge(),
                Tables\Columns\TextColumn::make('link_type')->label('لینک')->toggleable(),
                Tables\Columns\TextColumn::make('position')->label('ترتیب')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
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
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
