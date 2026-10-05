<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\UserEditTabs;
use App\Filament\Support\AdminTable;
use App\Filament\Support\ShopMediaPicker;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'کاربران';

    protected static ?string $modelLabel = 'کاربر';

    protected static ?string $pluralModelLabel = 'کاربران';

    protected static ?string $navigationGroup = 'کاربران';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return AdminAccess::canAccessAdminResource(static::class);
    }

    public static function canCreate(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public static function canDelete($record): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public static function canEdit($record): bool
    {
        if (AdminAccess::canManageShopInAdmin()) {
            return true;
        }

        return AdminAccess::isSalesManagerOnly();
    }

    public static function form(Form $form): Form
    {
        return $form->schema(function (Form $form): array {
            $operation = $form->getOperation();

            if ($operation === 'create') {
                return static::createFormSchema();
            }

            $activeTab = UserEditTabs::resolveActiveForForm($form, $operation);

            return [
                Forms\Components\View::make('user_edit_tab_nav')
                    ->view('filament.users.edit-tab-nav')
                    ->dehydrated(false)
                    ->columnSpanFull()
                    ->viewData([
                        'tabs' => UserEditTabs::definitions($operation),
                        'activeTab' => $activeTab,
                        'record' => $form->getRecord(),
                    ]),
                ...static::schemaForTab($activeTab),
            ];
        });
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function createFormSchema(): array
    {
        return [
            ...static::profileTabSchema(),
            ...static::accountTabSchema(),
            Forms\Components\Section::make('نوع کاربر و دسترسی')
                ->description('نقش‌ها و تنظیمات نمایندگی')
                ->schema(static::accessFieldsSchema(includeRepresentativeProfile: true, forCreate: true))
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function schemaForTab(string $tab): array
    {
        return match ($tab) {
            UserEditTabs::TAB_ACCOUNT => static::accountTabSchema(),
            UserEditTabs::TAB_ORDERS => static::ordersTabSchema(),
            UserEditTabs::TAB_PAYMENTS => static::paymentsTabSchema(),
            UserEditTabs::TAB_ACCESS => static::accessTabSchema(),
            default => static::profileTabSchema(),
        };
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function profileTabSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->description('تصویر، نام و معرفی کاربر')
                ->schema([
                    Forms\Components\Grid::make()
                        ->schema([
                            ShopMediaPicker::image('avatar', 'avatars', 'تصویر پروفایل')
                                ->columnSpan(['default' => 12, 'lg' => 4]),
                            Forms\Components\Group::make()
                                ->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label('نام و نام خانوادگی')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Textarea::make('bio')
                                        ->label('بیوگرافی / معرفی کوتاه')
                                        ->rows(3)
                                        ->maxLength(500)
                                        ->placeholder('برای نویسندگان و پروفایل عمومی نمایش داده می‌شود.'),
                                ])
                                ->columnSpan(['default' => 12, 'lg' => 8]),
                        ])
                        ->columns(12),
                ]),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function accountTabSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->description('راه‌های ارتباطی و ورود')
                ->schema([
                    Forms\Components\TextInput::make('phone')
                        ->label('موبایل')
                        ->required()
                        ->tel()
                        ->maxLength(15)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                            ? app(OtpService::class)->normalizePhone($state)
                            : null)
                        ->rule('regex:/^09\d{9}$/')
                        ->validationMessages([
                            'regex' => 'شماره موبایل باید با 09 شروع شود و ۱۱ رقم باشد.',
                        ])
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('email')
                        ->label('ایمیل')
                        ->email()
                        ->unique(ignoreRecord: true),
                    Forms\Components\TextInput::make('password')
                        ->label('رمز عبور')
                        ->password()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->helperText('در ویرایش، فقط در صورت تغییر رمز پر کنید.'),
                    Forms\Components\Toggle::make('status')
                        ->label('حساب فعال')
                        ->default(true),
                    Forms\Components\Placeholder::make('created_by_representative_info')
                        ->label('ثبت توسط نماینده')
                        ->content(function (?User $record): string {
                            if (! $record?->created_by_representative_id) {
                                return '—';
                            }

                            $rep = $record->createdByRepresentative;
                            if (! $rep) {
                                return 'شناسه نماینده: '.$record->created_by_representative_id;
                            }

                            $phone = $rep->phone ? ' — '.$rep->phone : '';

                            return $rep->name.$phone;
                        })
                        ->visible(fn (?User $record): bool => (bool) $record?->created_by_representative_id),
                ])
                ->columns(2),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function ordersTabSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->description('سفارش‌های ثبت‌شده با این حساب (حداکثر ۱۰۰ مورد اخیر)')
                ->schema([
                    Forms\Components\Placeholder::make('user_orders_list')
                        ->label('')
                        ->content(fn (?User $record): HtmlString => static::renderOrdersTable($record)),
                ])
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function paymentsTabSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->description('تراکنش‌های پرداخت این کاربر (حداکثر ۱۰۰ مورد اخیر)')
                ->schema([
                    Forms\Components\Placeholder::make('user_payments_list')
                        ->label('')
                        ->content(fn (?User $record): HtmlString => static::renderPaymentsTable($record)),
                ])
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function accessTabSchema(): array
    {
        return [
            Forms\Components\Section::make()
                ->description('مشتری یا نقش‌های سازمانی')
                ->schema(static::accessFieldsSchema(includeRepresentativeProfile: true, forCreate: false))
                ->columns(1),
        ];
    }

    /**
     * @return array<int, Forms\Components\Component>
     */
    protected static function accessFieldsSchema(bool $includeRepresentativeProfile, bool $forCreate): array
    {
        $fields = [
            Forms\Components\Select::make('user_kind')
                ->label('نوع کاربر')
                ->options([
                    'customer' => 'مشتری',
                    'staff' => 'غیر مشتری (مدیر، نویسنده، نماینده و ...)',
                ])
                ->default('customer')
                ->required()
                ->live(),
            Forms\Components\Fieldset::make('نقش‌های غیر مشتری')
                ->schema([
                    Forms\Components\Toggle::make('is_admin')
                        ->label('مدیر — دسترسی پنل ادمین'),
                    Forms\Components\Toggle::make('is_author')
                        ->label('نویسنده — انتشار در بلاگ'),
                    Forms\Components\Toggle::make('is_representative')
                        ->label('نماینده — پنل نمایندگی')
                        ->live(),
                    Forms\Components\Toggle::make('is_sales_manager')
                        ->label('مدیر فروش — سفارش‌ها، پرداخت‌ها، کاربران و مدیریت کامل محصولات')
                        ->disabled(fn (): bool => AdminAccess::isSalesManagerOnly()),
                ])
                ->columns(2)
                ->visible(fn (Get $get): bool => $get('user_kind') === 'staff'),
        ];

        if (! $forCreate) {
            $fields[] = Forms\Components\Placeholder::make('staff_roles_summary')
                ->label('نقش‌های فعال (ذخیره‌شده)')
                ->content(fn (?User $record): string => $record && ! $record->isCustomer()
                    ? $record->staffRoleLabel()
                    : '—')
                ->visible(fn (?User $record): bool => $record && ! $record->isCustomer());

            $fields[] = Forms\Components\Placeholder::make('representative_location_summary')
                ->label('خلاصه نمایندگی (ذخیره‌شده)')
                ->content(function (?User $record): string {
                    if (! $record?->isRepresentative()) {
                        return '—';
                    }

                    $profile = $record->representativeProfile;
                    if (! $profile) {
                        return 'پروفایل نمایندگی هنوز ثبت نشده — نقش نماینده را ذخیره کنید و استان/شهر را تکمیل کنید.';
                    }

                    $province = $profile->province?->name ?? '—';
                    $city = $profile->city?->name ?? '—';
                    $cap = $profile->max_active_reservations;

                    return "استان: {$province} · شهر: {$city} · سقف رزرو همزمان: {$cap}";
                })
                ->visible(fn (?User $record): bool => (bool) $record?->isRepresentative());
        }

        if ($includeRepresentativeProfile && Schema::hasTable('provinces')) {
            if ($forCreate) {
                $fields[] = Forms\Components\Fieldset::make('پروفایل نمایندگی')
                    ->schema(static::representativeProfileFieldsForCreate())
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('user_kind') === 'staff' && (bool) $get('is_representative'));
            } else {
                $fields[] = Forms\Components\Fieldset::make('پروفایل نمایندگی')
                    ->relationship('representativeProfile')
                    ->schema(static::representativeProfileFieldsForEdit())
                    ->columns(2)
                    ->visible(fn (Get $get): bool => $get('user_kind') === 'staff' && (bool) $get('is_representative'));
            }
        }

        return $fields;
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function representativeProfileFieldsForEdit(): array
    {
        return [
            Forms\Components\Select::make('province_id')
                ->label('استان فعالیت')
                ->relationship('province', 'name', fn ($query) => $query->orderBy('position'))
                ->searchable()
                ->preload()
                ->live(),
            Forms\Components\Select::make('city_id')
                ->label('شهر فعالیت')
                ->relationship(
                    'city',
                    'name',
                    fn ($query, Get $get) => $query
                        ->when($get('province_id'), fn ($q, $provinceId) => $q->where('province_id', $provinceId))
                        ->orderBy('position')
                )
                ->searchable()
                ->preload()
                ->disabled(fn (Get $get): bool => ! $get('province_id')),
            Forms\Components\TextInput::make('max_active_reservations')
                ->label('سقف رزرو همزمان')
                ->numeric()
                ->default(3)
                ->minValue(1)
                ->maxValue(50)
                ->helperText('حداکثر پیش‌فاکتور باز با رزرو موجودی (پیش‌فرض ۳).'),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function representativeProfileFieldsForCreate(): array
    {
        return [
            Forms\Components\Select::make('rep_province_id')
                ->label('استان فعالیت')
                ->options(fn () => Province::query()->orderBy('position')->pluck('name', 'id'))
                ->searchable()
                ->live()
                ->dehydrated(false),
            Forms\Components\Select::make('rep_city_id')
                ->label('شهر فعالیت')
                ->options(fn (Get $get) => City::query()
                    ->when($get('rep_province_id'), fn ($q, $id) => $q->where('province_id', $id))
                    ->orderBy('position')
                    ->pluck('name', 'id'))
                ->searchable()
                ->disabled(fn (Get $get): bool => ! $get('rep_province_id'))
                ->dehydrated(false),
            Forms\Components\TextInput::make('rep_max_active_reservations')
                ->label('سقف رزرو همزمان')
                ->numeric()
                ->default(3)
                ->minValue(1)
                ->maxValue(50)
                ->dehydrated(false)
                ->helperText('حداکثر پیش‌فاکتور باز با رزرو موجودی (پیش‌فرض ۳).'),
        ];
    }

    protected static function renderOrdersTable(?User $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('<p class="text-sm text-gray-500">—</p>');
        }

        $orders = $record->orders()
            ->withCount('items')
            ->latest()
            ->limit(100)
            ->get();

        return new HtmlString(
            view('filament.users.partials.orders-table', ['orders' => $orders])->render()
        );
    }

    protected static function renderPaymentsTable(?User $record): HtmlString
    {
        if (! $record) {
            return new HtmlString('<p class="text-sm text-gray-500">—</p>');
        }

        $payments = $record->payments()
            ->with('order')
            ->latest()
            ->limit(100)
            ->get();

        return new HtmlString(
            view('filament.users.partials.payments-table', ['payments' => $payments])->render()
        );
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('createdByRepresentative');
    }

    public static function table(Table $table): Table
    {
        return AdminTable::configure($table)
            ->searchPlaceholder('جستجوی کاربر')
            ->columns([
                ViewColumn::make('name')
                    ->label('کاربر')
                    ->view('filament.tables.columns.user-cell')
                    ->searchable(['name', 'email', 'phone']),
                Tables\Columns\TextColumn::make('customer_type')
                    ->label('نوع')
                    ->state(fn (User $record) => $record->isCustomer()
                        ? $record->customerTypeLabel()
                        : $record->staffRoleLabel())
                    ->badge(fn (User $record): bool => $record->isCustomer())
                    ->color(fn (User $record) => $record->isCustomer() ? $record->roleColor() : null),
                Tables\Columns\TextColumn::make('createdByRepresentative.name')
                    ->label('نماینده ثبت‌کننده')
                    ->placeholder('—')
                    ->toggleable()
                    ->visible(fn (): bool => AdminAccess::canManageShopInAdmin() || AdminAccess::isSalesManagerOnly()),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخرین ورود')
                    ->since()
                    ->placeholder('هرگز'),
                Tables\Columns\TextColumn::make('phone_verified_at')
                    ->label('احراز موبایل')
                    ->formatStateUsing(fn ($state) => $state ? 'فعال' : 'غیرفعال')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ عضویت')
                    ->dateTime('Y/m/d H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_kind')
                    ->label('نوع کاربر')
                    ->options([
                        'customer' => 'مشتری',
                        'staff' => 'مدیران',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value'] ?? null) {
                            'customer' => $query->where('is_admin', false)->where('is_author', false)->where('is_representative', false),
                            'staff' => $query->where(fn ($q) => $q
                                ->where('is_admin', true)
                                ->orWhere('is_author', true)
                                ->orWhere('is_representative', true)
                                ->orWhere('is_sales_manager', true)),
                            default => $query,
                        };
                    }),
                Tables\Filters\TernaryFilter::make('status')->label('فعال'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('ویرایش')->iconButton(),
                Tables\Actions\DeleteAction::make()->label('حذف')->iconButton()
                    ->successNotificationTitle('حذف شد'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('حذف انتخاب‌شده‌ها')
                        ->successNotificationTitle('حذف شد'),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
