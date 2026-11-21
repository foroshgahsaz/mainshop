<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PageResource\Pages;
use App\Filament\Support\AdminShopPreviewToggle;
use App\Filament\Support\AdminTable;
use App\Filament\Support\RichContentEditor;
use App\Filament\Support\SeoFormSchema;
use App\Models\Page;
use App\Models\Product;
use App\Support\OrderedProductIds;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'صفحات';

    protected static ?string $navigationGroup = 'محتوا';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('محتوا')
                ->schema([
                    Forms\Components\TextInput::make('title')->label('عنوان')->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                    Forms\Components\TextInput::make('slug')->label('اسلاگ')->required()->unique(ignoreRecord: true)
                        ->helperText('آدرس فروشگاه: /pages/{اسلاگ} — مثلاً test برای بلک‌فرایدی'),
                    Forms\Components\Select::make('layout')
                        ->label('نوع صفحه')
                        ->options([
                            Page::LAYOUT_CONTENT => 'متن (CMS)',
                            Page::LAYOUT_PRODUCT_GRID => 'گرید محصولات (مناسبت / کمپین)',
                        ])
                        ->default(Page::LAYOUT_CONTENT)
                        ->live()
                        ->required(),
                    RichContentEditor::make('content', 'محتوا', 'pages'),
                    Forms\Components\Placeholder::make('content_grid_hint')
                        ->hiddenLabel()
                        ->content('در نوع «گرید محصولات»، متن بالای لیست اختیاری است.')
                        ->visible(fn (Get $get): bool => $get('layout') === Page::LAYOUT_PRODUCT_GRID),
                    Forms\Components\Select::make('product_ids')
                        ->label('محصولات صفحه')
                        ->multiple()
                        ->searchable()
                        ->helperText('تا '.OrderedProductIds::MAX_CURATED.' محصول؛ ترتیب انتخاب همان ترتیب نمایش است.')
                        ->getSearchResultsUsing(function (string $search): array {
                            return Product::query()
                                ->when($search !== '', fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
                                ->orderBy('name')
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->getOptionLabelsUsing(fn (array $values): array => Product::query()
                            ->whereIn('id', $values)
                            ->pluck('name', 'id')
                            ->all())
                        ->visible(fn (Get $get): bool => $get('layout') === Page::LAYOUT_PRODUCT_GRID)
                        ->required(fn (Get $get): bool => $get('layout') === Page::LAYOUT_PRODUCT_GRID),
                    Forms\Components\Toggle::make('is_active')->label('فعال')->default(true),
                    AdminShopPreviewToggle::make(),
                ])->columns(2),
            ...SeoFormSchema::contentSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('عنوان')->searchable(),
                Tables\Columns\TextColumn::make('layout')->label('نوع')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Page::LAYOUT_PRODUCT_GRID => 'گرید محصول',
                        default => 'متن',
                    }),
                Tables\Columns\TextColumn::make('slug')->label('اسلاگ')->copyable(),
                Tables\Columns\IconColumn::make('is_active')->label('فعال')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->label('به‌روزرسانی')->shopJalaliDate(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPages::route('/'),
            'create' => Pages\CreatePage::route('/create'),
            'edit' => Pages\EditPage::route('/{record}/edit'),
        ];
    }
}
