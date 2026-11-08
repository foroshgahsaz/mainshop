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

    protected static ?string $navigationLabel = 'لینک مگا منو';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'لینک مگا منو';

    protected static ?string $pluralModelLabel = 'لینک‌های مگا منو';

    public static function schemaReady(): bool
    {
        return Schema::hasTable('mega_menu_links')
            && Schema::hasColumn('product_families', 'show_in_mega')
            && Schema::hasColumn('categories', 'product_family_id');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::schemaReady() && parent::shouldRegisterNavigation();
    }

    public static function canViewAny(): bool
    {
        return static::schemaReady() && AdminAccess::canManageShopInAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('لینک دستی مگا منو')
                ->description('ستون سفارشی: «ستون دسته» خالی و «لینک والد» خالی. زیرمجموعه: لینک والد را انتخاب کنید. زیر یک دسته: ستون دسته را پر کنید (بدون لینک والد).')
                ->schema([
                    Forms\Components\Select::make('product_family_id')
                        ->label('خانواده محصول')
                        ->relationship('family', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),
                    Forms\Components\Select::make('parent_id')
                        ->label('لینک والد (ستون سفارشی)')
                        ->options(fn (Get $get): array => MegaMenuLink::query()
                            ->where('product_family_id', $get('product_family_id'))
                            ->whereNull('category_id')
                            ->whereNull('parent_id')
                            ->orderBy('position')
                            ->pluck('label', 'id')
                            ->all())
                        ->searchable()
                        ->placeholder('—')
                        ->hidden(fn (Get $get) => filled($get('category_id'))),
                    Forms\Components\Select::make('category_id')
                        ->label('ستون دسته (لینک زیر دسته)')
                        ->options(fn (Get $get): array => Category::query()
                            ->where('product_family_id', $get('product_family_id'))
                            ->whereNull('parent_id')
                            ->orderBy('position')
                            ->pluck('name', 'id')
                            ->all())
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
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('family.name')->label('خانواده')->sortable(),
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
