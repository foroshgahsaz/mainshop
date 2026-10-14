<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesRegionResource\Pages;
use App\Filament\Support\AdminTable;
use App\Models\Province;
use App\Models\SalesRegion;
use App\Models\User;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesRegionResource extends Resource
{
    protected static ?string $model = SalesRegion::class;

    protected static ?string $slug = 'sales-regions';

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'مناطق فروش';

    protected static ?string $modelLabel = 'منطقه فروش';

    protected static ?string $pluralModelLabel = 'مناطق فروش';

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات منطقه')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('نام منطقه')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Select::make('sales_manager_id')
                        ->label('مدیر منطقه')
                        ->options(fn () => User::query()->where('is_sales_manager', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required()
                        ->helperText('از بین کاربرانی که نقش «مدیر فروش» دارند. یک مدیر می‌تواند چند منطقه داشته باشد.'),
                    Forms\Components\Select::make('province_ids')
                        ->label('استان‌ها')
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->options(fn (?SalesRegion $record) => Province::query()
                            ->where(fn (Builder $q) => $q
                                ->whereNull('sales_region_id')
                                ->when($record?->exists, fn (Builder $q) => $q->orWhere('sales_region_id', $record->id)))
                            ->orderBy('position')
                            ->pluck('name', 'id'))
                        ->helperText('هر استان فقط در یک منطقه؛ استان‌های منطقه‌های دیگر نمایش داده نمی‌شوند. نمایندگان این استان‌ها به مدیر این منطقه منتقل می‌شوند.')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Forms\Components\Section::make('نمایندگان این منطقه')
                ->schema([
                    Forms\Components\View::make('region_representatives')
                        ->view('filament.sales-regions.region-representatives')
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->viewData(function (?SalesRegion $record): array {
                            if (! $record?->exists) {
                                return ['representatives' => collect()];
                            }

                            $record->loadMissing('provinces');

                            return [
                                'representatives' => app(SalesRegionService::class)
                                    ->representativesForRegion($record)
                                    ->orderBy('name')
                                    ->get(),
                            ];
                        }),
                ])
                ->visibleOn('edit')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['salesManager:id,name', 'provinces:id,name,sales_region_id']))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('منطقه')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('salesManager.name')->label('مدیر منطقه')->placeholder('—'),
                Tables\Columns\TextColumn::make('provinces.name')
                    ->label('استان‌ها')
                    ->badge()
                    ->wrap(),
                Tables\Columns\TextColumn::make('representatives_count')
                    ->label('نمایندگان')
                    ->state(fn (SalesRegion $record): int => app(SalesRegionService::class)
                        ->representativesForRegion($record)
                        ->count()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesRegions::route('/'),
            'create' => Pages\CreateSalesRegion::route('/create'),
            'edit' => Pages\EditSalesRegion::route('/{record}/edit'),
        ];
    }
}
