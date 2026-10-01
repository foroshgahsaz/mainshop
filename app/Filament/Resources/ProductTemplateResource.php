<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductTemplateResource\Pages;
use App\Filament\Support\AdminTable;
use App\Models\ProductTemplate;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class ProductTemplateResource extends Resource
{
    protected static ?string $model = ProductTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'قالب محصول';

    protected static ?string $navigationGroup = 'فروشگاه';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $modelLabel = 'قالب محصول';

    protected static ?string $pluralModelLabel = 'قالب‌های محصول';

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات قالب')
                ->schema([
                    Forms\Components\Select::make('brand_id')
                        ->label('برند')
                        ->relationship('brand', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Forms\Components\Select::make('product_family_id')
                        ->label('خانواده محصول (اختیاری)')
                        ->relationship('productFamily', 'name')
                        ->searchable()
                        ->preload()
                        ->helperText('اگر انتخاب شود، این قالب فقط برای محصولات همان خانواده پیشنهاد می‌شود.'),
                    Forms\Components\TextInput::make('name')
                        ->label('نام قالب')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')
                        ->label('اسلاگ')
                        ->required()
                        ->maxLength(255),
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
                Tables\Columns\TextColumn::make('brand.name')->label('برند')->sortable(),
                Tables\Columns\TextColumn::make('productFamily.name')->label('خانواده')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('position')->label('ترتیب')->sortable(),
                Tables\Columns\TextColumn::make('products_count')->counts('products')->label('محصولات'),
            ])
            ->defaultSort('position')
            ->filters([
                Tables\Filters\SelectFilter::make('brand_id')
                    ->relationship('brand', 'name')
                    ->label('برند'),
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
            'index' => Pages\ListProductTemplates::route('/'),
            'create' => Pages\CreateProductTemplate::route('/create'),
            'edit' => Pages\EditProductTemplate::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['brand', 'productFamily']);
    }
}
