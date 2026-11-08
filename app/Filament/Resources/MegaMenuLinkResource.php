<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MegaMenuLinkResource\Pages;
use App\Filament\Support\AdminShopPreviewToggle;
use App\Filament\Support\AdminTable;
use App\Models\Category;
use App\Models\MegaMenuLink;
use App\Support\AdminAccess;
use Filament\Forms;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MegaMenuLinkResource extends Resource
{
    protected static ?string $model = MegaMenuLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'لینک مگا منو';

    protected static ?string $pluralModelLabel = 'لینک‌های مگا منو';

    public static function schemaReady(): bool
    {
        return Schema::hasTable('mega_menu_links')
            && Schema::hasColumn('categories', 'show_in_mega');
    }

    public static function canViewAny(): bool
    {
        return static::schemaReady() && AdminAccess::canManageShopInAdmin();
    }

    public static function form(Form $form): Form
    {
        $usesRootCategory = Schema::hasColumn('mega_menu_links', 'root_category_id');

        return $form->schema([
            Forms\Components\Section::make('لینک دستی مگا منو')
                ->description('ستون سفارشی: «ستون دسته» خالی و «لینک والد» خالی. زیرمجموعه: لینک والد را انتخاب کنید. زیر یک ستون دسته: ستون دسته را پر کنید (بدون لینک والد). ریل مگا منو = دستهٔ ریشه با show_in_mega.')
                ->schema([
                    $usesRootCategory
                        ? Forms\Components\Select::make('root_category_id')
                            ->label('ستون ریشه مگا (دستهٔ سطح ۱)')
                            ->options(fn (): array => Category::query()
                                ->whereNull('parent_id')
                                ->where('show_in_mega', true)
                                ->orderBy('position')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                        : Forms\Components\Select::make('product_family_id')
                            ->label('خانواده محصول (legacy)')
                            ->relationship('family', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live(),
                    Forms\Components\Select::make('parent_id')
                        ->label('لینک والد (ستون سفارشی)')
                        ->options(function (Get $get) use ($usesRootCategory): array {
                            $query = MegaMenuLink::query()
                                ->whereNull('category_id')
                                ->whereNull('parent_id')
                                ->orderBy('position');

                            if ($usesRootCategory && filled($get('root_category_id'))) {
                                $query->where('root_category_id', $get('root_category_id'));
                            } elseif (filled($get('product_family_id'))) {
                                $query->where('product_family_id', $get('product_family_id'));
                            }

                            return $query->pluck('label', 'id')->all();
                        })
                        ->searchable()
                        ->placeholder('—')
                        ->hidden(fn (Get $get) => filled($get('category_id'))),
                    Forms\Components\Select::make('category_id')
                        ->label('ستون دسته (سطح ۲ در همان ریشه)')
                        ->options(function (Get $get) use ($usesRootCategory): array {
                            if ($usesRootCategory && filled($get('root_category_id'))) {
                                return Category::query()
                                    ->where('parent_id', $get('root_category_id'))
                                    ->orderBy('position')
                                    ->pluck('name', 'id')
                                    ->all();
                            }

                            if (filled($get('product_family_id'))) {
                                return Category::query()
                                    ->where('product_family_id', $get('product_family_id'))
                                    ->whereNull('parent_id')
                                    ->orderBy('position')
                                    ->pluck('name', 'id')
                                    ->all();
                            }

                            return [];
                        })
                        ->searchable()
                        ->placeholder('—')
                        ->hidden(fn (Get $get) => filled($get('parent_id'))),
                    Forms\Components\TextInput::make('label')
                        ->label('عنوان')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('url')
                        ->label('آدرس (URL)')
                        ->required()
                        ->maxLength(2048)
                        ->placeholder('https://... یا /products')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('position')
                        ->label('ترتیب')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true),
                    AdminShopPreviewToggle::make(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        $usesRootCategory = Schema::hasColumn('mega_menu_links', 'root_category_id');

        return AdminTable::configure($table)
            ->columns([
                $usesRootCategory
                    ? Tables\Columns\TextColumn::make('rootCategory.name')->label('ریشه مگا')->sortable()->placeholder('—')
                    : Tables\Columns\TextColumn::make('family.name')->label('خانواده')->sortable(),
                Tables\Columns\TextColumn::make('label')->label('عنوان')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('ستون دسته')->placeholder('—'),
                Tables\Columns\TextColumn::make('parent.label')->label('والد')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('position')->label('ترتیب')->sortable(),
            ])
            ->defaultSort('position')
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('حذف')->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMegaMenuLinks::route('/'),
            'create' => Pages\CreateMegaMenuLink::route('/create'),
            'edit' => Pages\EditMegaMenuLink::route('/{record}/edit'),
        ];
    }
}
