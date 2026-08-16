<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageIntegrations extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'درگاه‌ها و پیامک';

    protected static ?string $title = 'تنظیمات درگاه پرداخت و پیامک';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-integrations';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $zarinpal = $settings->zarinpal();
        $tara = $settings->tara();
        $kavenegar = $settings->kavenegar();

        $this->form->fill([
            'zarinpal_enabled' => $zarinpal['enabled'],
            'zarinpal_merchant_id' => $zarinpal['merchant_id'],
            'zarinpal_sandbox' => $zarinpal['sandbox'],
            'zarinpal_callback_url' => $zarinpal['callback_url'],
            'zarinpal_amount_unit' => $zarinpal['amount_unit'],
            'tara_enabled' => $tara['enabled'],
            'tara_sandbox' => $tara['sandbox'],
            'tara_username' => $tara['username'],
            'tara_password' => $tara['password'],
            'tara_service_id' => $tara['service_id'],
            'tara_amount_unit' => $tara['amount_unit'],
            'tara_callback_url' => $tara['callback_url'],
            'tara_client_ip' => $tara['client_ip'],
            'tara_default_group' => $tara['default_group'],
            'tara_default_group_title' => $tara['default_group_title'],
            'tara_sandbox_base_url' => $tara['sandbox_base_url'],
            'tara_base_url' => $tara['production_base_url'],
            'tara_refund_principal' => $tara['refund_principal'],
            'tara_refund_password' => $tara['refund_password'],
            'tara_sandbox_refund_base_url' => $tara['sandbox_refund_base_url'],
            'tara_refund_base_url' => $tara['production_refund_base_url'],
            'kavenegar_enabled' => $kavenegar['enabled'],
            'kavenegar_api_key' => $kavenegar['api_key'],
            'kavenegar_sender' => $kavenegar['sender'],
            'kavenegar_otp_template' => $kavenegar['otp_template'],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('integrations')->tabs([
                    Forms\Components\Tabs\Tab::make('زرین‌پال')
                        ->icon('heroicon-o-banknotes')
                        ->schema([
                            Forms\Components\Toggle::make('zarinpal_enabled')
                                ->label('فعال‌سازی زرین‌پال')
                                ->helperText('درگاه نقدی')
                                ->default(true),
                            Forms\Components\TextInput::make('zarinpal_merchant_id')
                                ->label('Merchant ID')
                                ->placeholder('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
                            Forms\Components\Toggle::make('zarinpal_sandbox')
                                ->label('حالت Sandbox (تست)')
                                ->default(true),
                            Forms\Components\Select::make('zarinpal_amount_unit')
                                ->label('واحد مبلغ فروشگاه')
                                ->options([
                                    'toman' => 'تومان (ارسال به درگاه به‌صورت ریال ×۱۰)',
                                    'rial' => 'ریال (ارسال بدون تبدیل)',
                                ])
                                ->required(),
                            Forms\Components\TextInput::make('zarinpal_callback_url')
                                ->label('آدرس Callback')
                                ->placeholder('/payment/callback')
                                ->helperText('مسیر بازگشت از درگاه پس از پرداخت'),
                        ])->columns(2),
                    Forms\Components\Tabs\Tab::make('تارا')
                        ->icon('heroicon-o-wallet')
                        ->schema([
                            Forms\Components\Fieldset::make('عمومی')->schema([
                                Forms\Components\Toggle::make('tara_enabled')
                                    ->label('فعال‌سازی تارا')
                                    ->helperText('درگاه اعتباری؛ کاربر می‌تواند بخشی از مبلغ را با اعتبار تارا بپردازد'),
                                Forms\Components\Toggle::make('tara_sandbox')
                                    ->label('حالت Sandbox (تست)')
                                    ->default(true),
                                Forms\Components\Select::make('tara_amount_unit')
                                    ->label('واحد مبلغ فروشگاه')
                                    ->options([
                                        'toman' => 'تومان (ارسال به تارا به‌صورت ریال ×۱۰)',
                                        'rial' => 'ریال (ارسال بدون تبدیل)',
                                    ])
                                    ->required()
                                    ->helperText('مبالغ فروشگاه تومان است؛ برای تارا معمولاً تومان را انتخاب کنید'),
                                Forms\Components\TextInput::make('tara_callback_url')
                                    ->label('آدرس Callback')
                                    ->placeholder('/payment/callback/tara'),
                                Forms\Components\TextInput::make('tara_client_ip')
                                    ->label('IP سرور پذیرنده')
                                    ->placeholder('مثال: 1.2.3.4')
                                    ->helperText('IP سفیدشده نزد تارا. اگر خالی باشد از IP درخواست استفاده می‌شود'),
                            ])->columns(2),
                            Forms\Components\Fieldset::make('خرید آنلاین')->schema([
                                Forms\Components\TextInput::make('tara_username')
                                    ->label('نام کاربری (Username)')
                                    ->placeholder('tara_ipg'),
                                Forms\Components\TextInput::make('tara_password')
                                    ->label('رمز عبور')
                                    ->password()
                                    ->revealable(),
                                Forms\Components\TextInput::make('tara_service_id')
                                    ->label('Service ID')
                                    ->placeholder('101'),
                                Forms\Components\TextInput::make('tara_default_group')
                                    ->label('کد گروه کالایی تارا')
                                    ->placeholder('1'),
                                Forms\Components\TextInput::make('tara_default_group_title')
                                    ->label('عنوان گروه کالایی')
                                    ->placeholder('عمومی'),
                                Forms\Components\TextInput::make('tara_sandbox_base_url')
                                    ->label('Base URL تست خرید')
                                    ->placeholder('https://stage-pay.tara360.ir/pay')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('tara_base_url')
                                    ->label('Base URL عملیاتی خرید')
                                    ->placeholder('https://pay.tara360.ir/pay')
                                    ->columnSpanFull(),
                            ])->columns(2),
                            Forms\Components\Fieldset::make('مرجوعی')->schema([
                                Forms\Components\TextInput::make('tara_refund_principal')
                                    ->label('Principal مرجوعی')
                                    ->placeholder('ipg_refund'),
                                Forms\Components\TextInput::make('tara_refund_password')
                                    ->label('رمز مرجوعی')
                                    ->password()
                                    ->revealable(),
                                Forms\Components\TextInput::make('tara_sandbox_refund_base_url')
                                    ->label('Base URL تست مرجوعی')
                                    ->placeholder('https://stage.tara-club.ir/club')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('tara_refund_base_url')
                                    ->label('Base URL عملیاتی مرجوعی')
                                    ->placeholder('https://club.tara-club.ir/club')
                                    ->columnSpanFull(),
                            ])->columns(2),
                        ]),
                    Forms\Components\Tabs\Tab::make('کاوه‌نگار')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            Forms\Components\Toggle::make('kavenegar_enabled')
                                ->label('فعال‌سازی کاوه‌نگار')
                                ->helperText('برای OTP ورود کاربران'),
                            Forms\Components\TextInput::make('kavenegar_api_key')
                                ->label('API Key')
                                ->password()
                                ->revealable(),
                            Forms\Components\TextInput::make('kavenegar_sender')
                                ->label('شماره خط (Sender)')
                                ->placeholder('10004346'),
                            Forms\Components\TextInput::make('kavenegar_otp_template')
                                ->label('نام قالب OTP (Lookup)')
                                ->placeholder('verify')
                                ->helperText('نام قالبی که در پنل کاوه‌نگار ساختید'),
                        ])->columns(2),
                ]),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('zarinpal', [
            'enabled' => $data['zarinpal_enabled'] ?? false,
            'merchant_id' => $data['zarinpal_merchant_id'] ?? '',
            'sandbox' => $data['zarinpal_sandbox'] ?? true,
            'callback_url' => $data['zarinpal_callback_url'] ?? '/payment/callback',
            'amount_unit' => $data['zarinpal_amount_unit'] ?? 'toman',
        ]);

        $settings->setMany('tara', [
            'enabled' => $data['tara_enabled'] ?? false,
            'sandbox' => $data['tara_sandbox'] ?? true,
            'username' => $data['tara_username'] ?? '',
            'password' => $data['tara_password'] ?? '',
            'service_id' => $data['tara_service_id'] ?? '',
            'amount_unit' => $data['tara_amount_unit'] ?? 'toman',
            'callback_url' => $data['tara_callback_url'] ?? '/payment/callback/tara',
            'client_ip' => $data['tara_client_ip'] ?? '',
            'default_group' => $data['tara_default_group'] ?? '1',
            'default_group_title' => $data['tara_default_group_title'] ?? 'عمومی',
            'sandbox_base_url' => $data['tara_sandbox_base_url'] ?? '',
            'base_url' => $data['tara_base_url'] ?? '',
            'refund_principal' => $data['tara_refund_principal'] ?? '',
            'refund_password' => $data['tara_refund_password'] ?? '',
            'sandbox_refund_base_url' => $data['tara_sandbox_refund_base_url'] ?? '',
            'refund_base_url' => $data['tara_refund_base_url'] ?? '',
        ]);

        $settings->setMany('kavenegar', [
            'enabled' => $data['kavenegar_enabled'] ?? false,
            'api_key' => $data['kavenegar_api_key'] ?? '',
            'sender' => $data['kavenegar_sender'] ?? '',
            'otp_template' => $data['kavenegar_otp_template'] ?? 'verify',
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات با موفقیت ذخیره شد.')
            ->send();
    }
}
