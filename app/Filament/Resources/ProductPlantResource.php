<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductPlantResource\Pages;
use App\Filament\Support\AdminTable;
use App\Models\ProductPlant;
use Filament\Forms;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductPlantResource extends Resource
{
    protected static ?string $model = ProductPlant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'کارخانه';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'کارخانه';

    protected static ?string $pluralModelLabel = 'کارخانه‌ها';

    public static function canViewAny(): bool
    {
        return Schema::hasColumn('products', 'product_plant_id');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات کارخانه')
                ->schema([
                    Forms\Components\Select::make('product_family_id')
                        ->label('خانواده محصول')
                        ->relationship('productFamily', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('اختیاری؛ برای فیلتر در ثبت سفارش و فرم محصول.'),
                    Forms\Components\TextInput::make('name')
                        ->label('نام')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')
                        ->label('اسلاگ')
                        ->required()
                        ->maxLength(191)
                        ->unique(ignoreRecord: true),
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
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('productFamily.name')->label('خانواده')->placeholder('—'),
                Tables\Columns\TextColumn::make('slug')->label('اسلاگ'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('position')->label('ترتیب')->sortable(),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('محصولات'),
            ])
            ->defaultSort('position')
            ->filters([
                Tables\Filters\SelectFilter::make('product_family_id')
                    ->relationship('productFamily', 'name')
                    ->label('خانواده'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('حذف')->iconButton()
                    ->successNotificationTitle('حذف شد'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductPlants::route('/'),
            'create' => Pages\CreateProductPlant::route('/create'),
            'edit' => Pages\EditProductPlant::route('/{record}/edit'),
        ];
    }
}
