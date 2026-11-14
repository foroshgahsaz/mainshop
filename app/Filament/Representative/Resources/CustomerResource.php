<?php

namespace App\Filament\Representative\Resources;

use App\Filament\Representative\Resources\CustomerResource\Pages;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use App\Rules\IranianNationalCode;
use App\Services\Representative\RepresentativeCustomerService;
use App\Services\Representative\SalesRegionService;
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
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (?string $state, $livewire): void {
                            if (method_exists($livewire, 'lookupNationalCode')) {
                                $livewire->lookupNationalCode($state);
                            }
                        })
                        ->helperText('اگر مشتری با این کد ملی موجود باشد، نمایندگی فعلی نمایش داده می‌شود.'),
                    Forms\Components\View::make('customer_transfer_panel')
                        ->view('filament.representative.partials.customer-transfer')
                        ->viewData(fn ($component): array => [
                            'existingCustomerMessage' => $component->getLivewire()->existingCustomerMessage ?? null,
                            'canTransferExistingCustomer' => (bool) ($component->getLivewire()->canTransferExistingCustomer ?? false),
                            'transferOtpSent' => (bool) ($component->getLivewire()->transferOtpSent ?? false),
                        ])
                        ->dehydrated(false)
                        ->columnSpanFull()
                        ->visible(fn ($livewire): bool => filled($livewire->existingCustomerMessage ?? null)),
                ])
                ->columns(2),
            Forms\Components\Section::make('آدرس')
                ->schema([
                    Forms\Components\Select::make('province_id')
                        ->label('استان')
                        ->options(function (): array {
                            $provinceId = static::lockedActivityProvinceId();
                            if ($provinceId === null) {
                                return [];
                            }

                            return Province::query()
                                ->whereKey($provinceId)
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->default(fn (): ?int => static::lockedActivityProvinceId())
                        ->required()
                        ->disabled()
                        ->dehydrated()
                        ->helperText('استان آدرس همان استان فعالیت شماست و قابل تغییر نیست.')
                        ->live(),
                    Forms\Components\Select::make('city_id')
                        ->label('شهر')
                        ->options(function (Get $get): array {
                            $provinceId = (int) ($get('province_id') ?: static::lockedActivityProvinceId() ?: 0);

                            return City::query()
                                ->when($provinceId > 0, fn ($q) => $q->where('province_id', $provinceId))
                                ->orderBy('position')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->required()
                        ->searchable()
                        ->disabled(fn (): bool => static::lockedActivityProvinceId() === null),
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

    protected static function lockedActivityProvinceId(): ?int
    {
        $user = auth()->user();

        return $user instanceof User
            ? app(SalesRegionService::class)->activityProvinceIdForRepresentative($user)
            : null;
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
                    ->shopJalaliDate()
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
