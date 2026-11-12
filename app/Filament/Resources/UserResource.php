<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\UserEditTabs;
use App\Filament\Support\AdminTable;
use App\Filament\Support\UserTableColumns;
use App\Filament\Support\ShopMediaPicker;
use App\Models\City;
use App\Models\Province;
use App\Models\RepresentativeProfile;
use App\Models\User;
use App\Rules\IranianNationalCode;
use App\Services\Auth\OtpService;
use App\Services\Representative\SalesRegionService;
use App\Support\AdminAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
        return AdminAccess::canCreateRepresentativesInAdmin()
            || AdminAccess::canCreateBankdarsInAdmin();
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

        if (! $record instanceof User) {
            return false;
        }

        // پروفایل خود کاربر (مدیر فروش / بنکدار / نماینده)
        if ((int) auth()->id() === (int) $record->getKey()
            && (AdminAccess::isLimitedStaffOnly() || AdminAccess::isRepresentativeProfileOnly())
        ) {
            return true;
        }

        if (! AdminAccess::isLimitedStaffOnly()) {
            return false;
        }

        $actor = auth()->user();

        if ($record->exists && ! $record->relationLoaded('representativeProfile')) {
            $record->loadMissing('representativeProfile');
        }

        return $actor instanceof User
            && app(SalesRegionService::class)->managesUser($actor, $record);
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
        if (AdminAccess::isSalesManagerOnly()) {
            return static::createBankdarFormSchemaForSalesManager();
        }

        if (AdminAccess::isBankdarOnly()) {
            return static::createRepresentativeFormSchemaForBankdar();
        }

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
    protected static function createBankdarFormSchemaForSalesManager(): array
    {
        return [
            ...static::profileTabSchema(),
            ...static::accountTabSchema(),
            Forms\Components\Hidden::make('user_kind')->default('staff'),
            Forms\Components\Hidden::make('is_bankdar')->default(true),
            Forms\Components\Hidden::make('is_representative')->default(false),
            Forms\Components\Hidden::make('is_admin')->default(false),
            Forms\Components\Hidden::make('is_author')->default(false),
            Forms\Components\Hidden::make('is_sales_manager')->default(false),
            Forms\Components\Section::make('استان بنکدار')
                ->description('هر منطقه/استان می‌تواند چند بنکدار داشته باشد. بنکدار فقط برای همین استان نماینده می‌سازد.')
                ->schema([
                    Forms\Components\Select::make('bankdar_province_id')
                        ->label('استان')
                        ->options(fn (): array => app(SalesRegionService::class)->provinceOptionsAvailableForBankdar())
                        ->in(fn (): array => app(SalesRegionService::class)->provinceIdsAvailableForBankdar())
                        ->validationMessages(['in' => 'استان باید در یکی از مناطق فروش تعریف شده باشد.'])
                        ->searchable()
                        ->required()
                        ->helperText('فقط استان‌هایی که مدیر سیستم در مناطق فروش ثبت کرده است.'),
                ])
                ->columnSpanFull(),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function createRepresentativeFormSchemaForBankdar(): array
    {
        $provinceId = (int) (auth()->user()?->bankdar_province_id ?? 0);
        $provinceName = $provinceId > 0
            ? (string) (Province::query()->whereKey($provinceId)->value('name') ?? '')
            : '';

        return [
            ...static::profileTabSchema(),
            ...static::accountTabSchema(),
            Forms\Components\Hidden::make('user_kind')->default('staff'),
            Forms\Components\Hidden::make('is_representative')->default(true),
            Forms\Components\Hidden::make('is_admin')->default(false),
            Forms\Components\Hidden::make('is_author')->default(false),
            Forms\Components\Hidden::make('is_sales_manager')->default(false),
            Forms\Components\Hidden::make('is_bankdar')->default(false),
            Forms\Components\Hidden::make('rep_province_id')->default($provinceId ?: null),
            Forms\Components\Section::make('پروفایل نمایندگی')
                ->description('این نماینده زیرمجموعه شما (بنکدار) ثبت می‌شود. استان فعالیت همان استان شماست و قابل تغییر نیست.')
                ->schema([
                    Forms\Components\Placeholder::make('locked_province')
                        ->label('استان فعالیت')
                        ->content($provinceName !== '' ? $provinceName : 'استان بنکدار تعیین نشده — با مدیر فروش هماهنگ کنید.'),
                    Forms\Components\Select::make('rep_city_id')
                        ->label('شهر فعالیت')
                        ->options(fn (): array => static::cityOptions($provinceId ?: null))
                        ->searchable()
                        ->required()
                        ->disabled($provinceId <= 0)
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('rep_max_active_reservations')
                        ->label('سقف رزرو همزمان')
                        ->numeric()
                        ->default(3)
                        ->minValue(1)
                        ->maxValue(50)
                        ->dehydrated(false)
                        ->helperText('حداکثر پیش‌فاکتور باز با رزرو موجودی (پیش‌فرض ۳).'),
                ])
                ->columns(2)
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
                                    Forms\Components\TextInput::make('slug')
                                        ->label('نامک (slug)')
                                        ->maxLength(191)
                                        ->unique(ignoreRecord: true)
                                        ->helperText('برای URL عمومی نویسنده؛ فقط حروف انگلیسی، عدد و خط تیره.')
                                        ->visible(fn (): bool => AdminAccess::canManageShopInAdmin()),
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
                    Forms\Components\TextInput::make('national_code')
                        ->label('کد ملی')
                        ->length(10)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state)
                            ? IranianNationalCode::normalize($state)
                            : null)
                        ->rules(fn (): array => [
                            'nullable',
                            new IranianNationalCode,
                        ])
                        ->unique(ignoreRecord: true)
                        ->helperText('برای پرداخت باجت‌پی باید با موبایل مشتری در باجت یکی باشد.'),
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
                    Forms\Components\Fieldset::make('تأیید و متادیتا (مدیر سیستم)')
                        ->schema([
                            Forms\Components\DateTimePicker::make('phone_verified_at')
                                ->label('زمان تأیید موبایل')
                                ->seconds(false)
                                ->native(false),
                            Forms\Components\DateTimePicker::make('email_verified_at')
                                ->label('زمان تأیید ایمیل')
                                ->seconds(false)
                                ->native(false),
                            Forms\Components\Select::make('created_by_representative_id')
                                ->label('ثبت‌شده توسط نماینده')
                                ->relationship(
                                    'createdByRepresentative',
                                    'name',
                                    fn ($query) => $query->where('is_representative', true)->orderBy('name')
                                )
                                ->searchable(['name', 'phone'])
                                ->preload()
                                ->nullable(),
                            Forms\Components\Placeholder::make('login_stats')
                                ->label('آمار ورود')
                                ->content(function (?User $record): string {
                                    if (! $record) {
                                        return '—';
                                    }

                                    $last = $record->last_login_at?->shopJalali('Y/m/d H:i') ?? 'هرگز';

                                    return "آخرین ورود: {$last} · تعداد: ".(int) $record->login_count;
                                }),
                        ])
                        ->columns(2)
                        ->visible(fn (): bool => AdminAccess::canManageShopInAdmin()),
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
                ->disabled(fn (): bool => AdminAccess::isLimitedStaffOnly())
                ->live(),
            Forms\Components\Fieldset::make('نقش‌های غیر مشتری')
                ->schema([
                    Forms\Components\Toggle::make('is_admin')
                        ->label('مدیر — دسترسی پنل ادمین')
                        ->disabled(fn (): bool => AdminAccess::isLimitedStaffOnly()),
                    Forms\Components\Toggle::make('is_author')
                        ->label('نویسنده — انتشار در بلاگ')
                        ->disabled(fn (): bool => AdminAccess::isLimitedStaffOnly()),
                    Forms\Components\Toggle::make('is_representative')
                        ->label('نماینده — پنل نمایندگی')
                        ->disabled(fn (): bool => AdminAccess::isLimitedStaffOnly())
                        ->live(),
                    Forms\Components\Toggle::make('is_sales_manager')
                        ->label('مدیر فروش — مدیریت بنکدارها و زیرمجموعه فروش')
                        ->disabled(fn (): bool => AdminAccess::isLimitedStaffOnly()),
                    Forms\Components\Toggle::make('is_bankdar')
                        ->label('بنکدار — مدیریت نمایندگان استان')
                        ->disabled(fn (): bool => AdminAccess::isLimitedStaffOnly())
                        ->live(),
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

                    $manager = $profile->salesManager?->name ?? 'تعیین نشده';
                    $region = $profile->province?->salesRegion?->name ?? '—';
                    $province = $profile->province?->name ?? '—';
                    $city = $profile->city?->name ?? '—';
                    $cap = $profile->max_active_reservations;

                    return "بنکدار: {$manager} · منطقه: {$region} · استان: {$province} · شهر: {$city} · سقف رزرو همزمان: {$cap}";
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
                    ->visible(function (Get $get, mixed $record): bool {
                        if ($get('user_kind') === 'staff' && (bool) $get('is_representative')) {
                            return true;
                        }

                        if ($record instanceof User) {
                            return $record->isRepresentative();
                        }

                        return $record instanceof RepresentativeProfile;
                    });
            }
        }

        return $fields;
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function representativeProfileFieldsForEdit(): array
    {
        $bankdarOnly = AdminAccess::isBankdarOnly();

        return [
            Forms\Components\Select::make('sales_manager_id')
                ->label('بنکدار')
                ->options(fn (Get $get, mixed $record): array => static::salesManagerOptions(
                    static::salesManagerIdFromProfileForm($get, $record)
                ))
                ->searchable()
                ->required()
                ->live()
                ->disabled($bankdarOnly)
                ->dehydrated(! $bankdarOnly)
                ->afterStateUpdated(fn (Set $set) => $set('province_id', null))
                ->helperText('بنکدار مسئول این نماینده. درخواست‌های رزرو مجدد برای همین بنکدار ارسال می‌شود.'),
            Forms\Components\Select::make('province_id')
                ->label('استان فعالیت')
                ->options(fn (Get $get, mixed $record): array => static::provinceOptionsForProfileForm($get, $record))
                ->in(fn (Get $get, mixed $record): array => static::provinceIdsForProfileForm($get, $record))
                ->validationMessages(['in' => 'استان فعالیت باید استان همین بنکدار باشد.'])
                ->searchable()
                ->required()
                ->live()
                ->disabled($bankdarOnly)
                ->dehydrated(! $bankdarOnly)
                ->afterStateUpdated(fn (Set $set) => $set('city_id', null))
                ->helperText($bankdarOnly ? 'استان بنکدار — غیرقابل تغییر.' : 'فقط استان همین بنکدار.'),
            Forms\Components\Select::make('city_id')
                ->label('شهر فعالیت')
                ->options(fn (Get $get, mixed $record): array => static::cityOptions(
                    $get('province_id') ?: static::representativeProfileFromFormRecord($record)?->province_id
                ))
                ->searchable()
                ->disabled(fn (Get $get, mixed $record): bool => ! ($get('province_id')
                    ?: static::representativeProfileFromFormRecord($record)?->province_id)),
            Forms\Components\TextInput::make('max_active_reservations')
                ->label('سقف رزرو همزمان')
                ->numeric()
                ->default(3)
                ->minValue(1)
                ->maxValue(50)
                ->helperText('حداکثر پیش‌فاکتور باز با رزرو موجودی (پیش‌فرض ۳).'),
        ];
    }

    protected static function representativeProfileFromFormRecord(mixed $record): ?RepresentativeProfile
    {
        if ($record instanceof RepresentativeProfile) {
            return $record;
        }

        if ($record instanceof User) {
            $record->loadMissing('representativeProfile');

            return $record->representativeProfile;
        }

        return null;
    }

    protected static function salesManagerIdFromProfileForm(Get $get, mixed $record): ?int
    {
        if (filled($get('sales_manager_id'))) {
            return (int) $get('sales_manager_id');
        }

        $fromProfile = static::representativeProfileFromFormRecord($record)?->sales_manager_id;

        return $fromProfile ? (int) $fromProfile : null;
    }

    /** @return array<int, string> */
    protected static function provinceOptionsForProfileForm(Get $get, mixed $record): array
    {
        $options = app(SalesRegionService::class)->provinceOptionsForManager(
            static::salesManagerIdFromProfileForm($get, $record)
        );

        $currentId = (int) ($get('province_id') ?: static::representativeProfileFromFormRecord($record)?->province_id);
        if ($currentId > 0 && ! isset($options[$currentId])) {
            $name = Province::query()->whereKey($currentId)->value('name');
            if (is_string($name) && $name !== '') {
                $options[$currentId] = $name.' (خارج از منطقه فعلی)';
            }
        }

        return $options;
    }

    /** @return list<int> */
    protected static function provinceIdsForProfileForm(Get $get, mixed $record): array
    {
        $ids = app(SalesRegionService::class)->provinceIdsForManager(
            static::salesManagerIdFromProfileForm($get, $record)
        );

        $currentId = (int) ($get('province_id') ?: static::representativeProfileFromFormRecord($record)?->province_id);
        if ($currentId > 0 && ! in_array($currentId, $ids, true)) {
            $ids[] = $currentId;
        }

        return $ids;
    }

    /** @return array<int, string> */
    protected static function salesManagerOptions(?int $currentId = null): array
    {
        $options = app(SalesRegionService::class)->managerOptions();

        if ($currentId && ! isset($options[$currentId])) {
            $name = User::query()->whereKey($currentId)->value('name');
            if ($name !== null) {
                $options[$currentId] = $name.' (بدون منطقه)';
            }
        }

        return $options;
    }

    /** @return array<int, string> */
    protected static function cityOptions(mixed $provinceId): array
    {
        if (! $provinceId) {
            return [];
        }

        return City::query()->where('province_id', $provinceId)->orderBy('position')->pluck('name', 'id')->all();
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function representativeProfileFieldsForCreate(bool $includeSalesManager = true): array
    {
        $fields = [];

        if ($includeSalesManager) {
            $fields[] = Forms\Components\Select::make('rep_sales_manager_id')
                ->label('بنکدار')
                ->options(fn (): array => static::salesManagerOptions())
                ->in(fn (): array => array_keys(static::salesManagerOptions()))
                ->searchable()
                ->required()
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(fn (Set $set) => $set('rep_province_id', null))
                ->helperText('بنکدار مسئول نماینده (مدیر فروش از کاربران → افزودن بنکدار).');
        }

        return [
            ...$fields,
            Forms\Components\Select::make('rep_province_id')
                ->label('استان فعالیت')
                ->options(fn (Get $get): array => app(SalesRegionService::class)->provinceOptionsForManager(
                    $includeSalesManager ? (int) $get('rep_sales_manager_id') : (int) auth()->id()
                ))
                ->in(fn (Get $get): array => app(SalesRegionService::class)->provinceIdsForManager(
                    $includeSalesManager ? (int) $get('rep_sales_manager_id') : (int) auth()->id()
                ))
                ->validationMessages(['in' => 'استان فعالیت باید استان همین بنکدار باشد.'])
                ->searchable()
                ->required()
                ->live()
                ->dehydrated(false)
                ->afterStateUpdated(fn (Set $set) => $set('rep_city_id', null))
                ->helperText($includeSalesManager ? 'فقط استان بنکدار انتخاب‌شده.' : 'استان شما (بنکدار).'),
            Forms\Components\Select::make('rep_city_id')
                ->label('شهر فعالیت')
                ->options(fn (Get $get): array => static::cityOptions($get('rep_province_id')))
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
        return parent::getEloquentQuery()->with([
            'createdByRepresentative',
            'representativeProfile.salesManager:id,name',
            'representativeProfile.province:id,name',
        ]);
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
                UserTableColumns::phone(),
                Tables\Columns\TextColumn::make('admin_list_segment')
                    ->label('دسته')
                    ->state(fn (User $record): string => $record->adminListSegmentLabel())
                    ->description(fn (User $record): ?string => $record->adminListSegmentSecondaryRolesDescription())
                    ->badge()
                    ->color(fn (User $record): string => $record->adminListSegmentColor()),
                Tables\Columns\TextColumn::make('national_code')
                    ->label('کد ملی')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->visible(fn (): bool => AdminAccess::canManageShopInAdmin()),
                Tables\Columns\TextColumn::make('representativeProfile.salesManager.name')
                    ->label('بنکدار')
                    ->description(fn (User $record): ?string => $record->representativeProfile?->province?->name)
                    ->placeholder(fn (User $record): string => $record->isRepresentative() ? 'تعیین نشده' : '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bankdarProvince.name')
                    ->label('استان بنکدار')
                    ->placeholder('—')
                    ->toggleable()
                    ->visible(fn (): bool => AdminAccess::canManageShopInAdmin() || AdminAccess::isSalesManagerOnly()),
                Tables\Columns\TextColumn::make('createdByRepresentative.name')
                    ->label('نماینده ثبت‌کننده')
                    ->placeholder('—')
                    ->toggleable()
                    ->visible(fn (): bool => AdminAccess::canManageShopInAdmin() || AdminAccess::isLimitedStaffOnly()),
                Tables\Columns\TextColumn::make('last_login_at')
                    ->label('آخرین ورود')
                    ->shopJalaliDateTime()
                    ->placeholder('هرگز'),
                Tables\Columns\TextColumn::make('phone_verified_at')
                    ->label('احراز موبایل')
                    ->formatStateUsing(fn ($state) => $state ? 'فعال' : 'غیرفعال')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاریخ عضویت')
                    ->shopJalaliDateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rep_sales_manager')
                    ->label('بنکدار نماینده')
                    ->options(fn (): array => ['none' => 'بدون بنکدار']
                        + User::query()->where('is_bankdar', true)->orderBy('name')->pluck('name', 'id')->all())
                    ->visible(fn (): bool => AdminAccess::canManageShopInAdmin())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if ($value === null || $value === '') {
                            return $query;
                        }

                        $query->where('is_representative', true);

                        return $value === 'none'
                            ? $query->whereDoesntHave('representativeProfile', fn (Builder $q) => $q->whereNotNull('sales_manager_id'))
                            : $query->whereHas('representativeProfile', fn (Builder $q) => $q->where('sales_manager_id', (int) $value));
                    }),
                Tables\Filters\SelectFilter::make('admin_list_segment')
                    ->label('دسته')
                    ->options(User::adminListSegmentFilterOptions())
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! is_string($value) || $value === '') {
                            return $query;
                        }

                        return $query->adminListSegment($value);
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
