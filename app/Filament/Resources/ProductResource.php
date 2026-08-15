<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Filament\Support\AdminTable;
use App\Filament\Support\Filters\PriceRangeFilter;
use App\Filament\Support\RichContentEditor;
use App\Filament\Support\SeoFormSchema;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'محصولات';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات اصلی')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('دسته‌بندی')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('brand_id')
                            ->label('برند')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('name')
                            ->label('نام محصول')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn($state, callable $set) =>
                                $set('slug', \Illuminate\Support\Str::slug($state))
                            ),

                        Forms\Components\TextInput::make('slug')
                            ->label('اسلاگ')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Textarea::make('short_description')
                            ->label('توضیحات کوتاه')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichContentEditor::make('description', 'توضیحات کامل', 'products'),
                    ])->columns(2),

                Forms\Components\Section::make('قیمت و موجودی')
                    ->schema([
                        Forms\Components\TextInput::make('price')
                            ->label('قیمت (تومان)')
                            ->numeric()
                            ->prefix('تومان')
                            ->required(),

                        Forms\Components\TextInput::make('sale_price')
                            ->label('قیمت فروش ویژه (تومان)')
                            ->numeric()
                            ->prefix('تومان')
                            ->helperText('در صورت وارد کردن، این قیمت به جای قیمت اصلی نمایش داده می‌شود'),

                        Forms\Components\TextInput::make('stock')
                            ->label('موجودی')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Forms\Components\TextInput::make('weight')
                            ->label('وزن (گرم)')
                            ->numeric()
                            ->suffix('گرم'),
                    ])->columns(2),

                Forms\Components\Section::make('وضعیت و نمایش')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('فعال')
                            ->default(true)
                            ->helperText('در صورت غیرفعال بودن، محصول در سایت نمایش داده نمی‌شود'),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('محصول ویژه')
                            ->default(false)
                            ->helperText('محصولات ویژه در بخش‌های خاصی از سایت نمایش داده می‌شوند'),
                    ])->columns(2),

                ...SeoFormSchema::productSection(),
            ]);
    }


    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('نام محصول')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('دسته‌بندی')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('قیمت')
                    ->money('irr')
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock')
                    ->label('موجودی')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('فعال')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('ویژه')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ایجاد')
                    ->dateTime('Y-m-d'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->relationship('category', 'name')
                    ->label('دسته‌بندی'),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('برند'),
                PriceRangeFilter::make('price', 10000000),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('وضعیت'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('محصول ویژه'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('حذف')->iconButton()
                    ->successNotificationTitle('حذف شد'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AttributesRelationManager::class,
            RelationManagers\VariantsRelationManager::class,
            RelationManagers\ImagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
