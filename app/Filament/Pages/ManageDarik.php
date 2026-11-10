<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\GatewayCheckoutDisplayFields;
use App\Filament\Support\GatewayHomepagePlanFields;
use App\Filament\Support\ShopIconUpload;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageDarik extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'داریک';

    protected static ?string $slug = 'darik';

    protected static ?string $title = 'تنظیمات درگاه اعتباری داریک (کیف پول)';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 7;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $darik = $settings->darik();

        $this->form->fill([
            'enabled' => $darik['enabled'],
            'sandbox' => $darik['sandbox'],
            'api_key' => $darik['api_key'],
            'username' => $darik['username'],
            'password' => $darik['password'],
            'merchant_code' => $darik['merchant_code'],
            'merchant_group_id' => $darik['merchant_group_id'],
            'province_id' => $darik['province_id'],
            'amount_unit' => $darik['amount_unit'],
            'sandbox_base_url' => $darik['sandbox_base_url'],
            'base_url' => $darik['production_base_url'],
            'icon' => ShopIconUpload::forForm($darik['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::mountState($darik),
            ...GatewayHomepagePlanFields::mountState($darik),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('عمومی')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی داریک')
                        ->helperText('پرداخت اعتباری با رمز ۵ رقمی پیامک‌شده (مستند docs/darik.pdf)'),
                    Forms\Components\Toggle::make('sandbox')
                        ->label('حالت تست')
                        ->default(true),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به API به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required(),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون درگاه')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('API و پذیرنده')->schema([
                    Forms\Components\TextInput::make('api_key')
                        ->label('ApiKey')
                        ->required(),
                    Forms\Components\TextInput::make('username')
                        ->label('نام کاربری Login')
                        ->required(),
                    Forms\Components\TextInput::make('password')
                        ->label('رمز عبور Login')
                        ->password()
                        ->revealable()
                        ->required(),
                    Forms\Components\TextInput::make('merchant_code')
                        ->label('کد فروشگاه (MerchantCode)')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('merchant_group_id')
                        ->label('نوع فروشگاه (MerchantGroupId)')
                        ->numeric()
                        ->default(2)
                        ->required(),
                    Forms\Components\TextInput::make('province_id')
                        ->label('کد استان پیش‌فرض (ProvinceId)')
                        ->numeric()
                        ->default(8)
                        ->helperText('اگر آدرس سفارش استان داشته باشد، در نسخه‌های بعدی نگاشت جداگانه اضافه می‌شود.')
                        ->required(),
                    Forms\Components\TextInput::make('sandbox_base_url')
                        ->label('Base URL تست')
                        ->placeholder('https://example.ir')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('base_url')
                        ->label('Base URL عملیاتی')
                        ->placeholder('https://example.ir')
                        ->columnSpanFull(),
                ])->columns(2),
                GatewayCheckoutDisplayFields::section(),
                GatewayHomepagePlanFields::section(),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('darik', [
            'enabled' => $data['enabled'] ?? false,
            'sandbox' => $data['sandbox'] ?? true,
            'api_key' => $data['api_key'] ?? '',
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'merchant_code' => (string) ($data['merchant_code'] ?? ''),
            'merchant_group_id' => (string) ($data['merchant_group_id'] ?? '2'),
            'province_id' => (string) ($data['province_id'] ?? '8'),
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'sandbox_base_url' => rtrim((string) ($data['sandbox_base_url'] ?? ''), '/'),
            'base_url' => rtrim((string) ($data['base_url'] ?? ''), '/'),
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::saveState($data),
            ...GatewayHomepagePlanFields::saveState($data),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات درگاه داریک با موفقیت ذخیره شد.')
            ->send();
    }
}
