<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductFamilyResource\Pages;
use App\Filament\Support\AdminImageColumn;
use App\Filament\Support\AdminTable;
use App\Filament\Support\ShopMediaPicker;
use App\Models\ProductFamily;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductFamilyResource extends Resource
{
    protected static ?string $model = ProductFamily::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'خانواده محصول';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'خانواده محصول';

    protected static ?string $pluralModelLabel = 'خانواده‌های محصول';

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات خانواده')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('نام')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')
                        ->label('اسلاگ')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    ShopMediaPicker::image('image', 'product-families', 'تصویر')->columnSpanFull(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('فعال')
                        ->default(true),
                    Forms\Components\TextInput::make('position')
                        ->label('ترتیب')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                AdminImageColumn::make('image')->label('تصویر'),
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('اسلاگ'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('position')->label('ترتیب')->sortable(),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('محصولات'),
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
            'index' => Pages\ListProductFamilies::route('/'),
            'create' => Pages\CreateProductFamily::route('/create'),
            'edit' => Pages\EditProductFamily::route('/{record}/edit'),
        ];
    }
}
