<?php

namespace App\Filament\Representative\Resources;

use App\Filament\Representative\Resources\CustomerResource\Pages;
use App\Models\City;
use App\Models\User;
use App\Rules\IranianNationalCode;
use App\Services\Representative\RepresentativeCustomerService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'مشتریان';

    protected static ?string $modelLabel = 'مشتری';

    protected static ?string $pluralModelLabel = 'مشتریان';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('اطلاعات مشتری')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('نام و نام خانوادگی')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('phone')
                        ->label('موبایل')
                        ->required()
                        ->tel()
                        ->maxLength(11)
                        ->unique(ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'مشتری با این شماره موبایل موجود است.',
                        ]),
                    Forms\Components\TextInput::make('national_code')
                        ->label('کد ملی')
                        ->required()
                        ->length(10)
                        ->rules([new IranianNationalCode])
                        ->unique(User::class, 'national_code', ignoreRecord: true)
                        ->validationMessages([
                            'unique' => 'مشتری با این کد ملی قبلاً ثبت شده است.',
                        ]),
                ])
                ->columns(2),
            Forms\Components\Section::make('آدرس')
                ->schema([
                    Forms\Components\Select::make('province_id')
                        ->label('استان')
                        ->options(fn () => \App\Models\Province::query()->orderBy('position')->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->live(),
                    Forms\Components\Select::make('city_id')
                        ->label('شهر')
                        ->options(fn (Get $get) => City::query()
                            ->when($get('province_id'), fn ($q, $id) => $q->where('province_id', $id))
                            ->orderBy('position')
                            ->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->disabled(fn (Get $get): bool => ! $get('province_id')),
                    Forms\Components\Textarea::make('address')
                        ->label('آدرس')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('postal_code')
                        ->label('کد پستی')
                        ->maxLength(10),
                ])
                ->columns(2)
                ->visibleOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('نام')->searchable(),
                Tables\Columns\TextColumn::make('phone')->label('موبایل')->searchable(),
                Tables\Columns\TextColumn::make('national_code')->label('کد ملی')->searchable(),
                Tables\Columns\TextColumn::make('addresses.city')
                    ->label('شهر')
                    ->formatStateUsing(fn (User $record) => $record->addresses->firstWhere('is_default', true)?->city
                        ?? $record->addresses->first()?->city
                        ?? '—'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ ثبت')
                    ->dateTime('Y-m-d')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make()->label('مشاهده'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $repId = auth()->id();

        return parent::getEloquentQuery()
            ->with('addresses')
            ->where('created_by_representative_id', $repId)
            ->where('is_admin', false)
            ->where('is_author', false)
            ->where('is_representative', false);
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
