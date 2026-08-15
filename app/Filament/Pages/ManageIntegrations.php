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
        $kavenegar = $settings->kavenegar();

        $this->form->fill([
            'zarinpal_enabled' => $zarinpal['enabled'],
            'zarinpal_merchant_id' => $zarinpal['merchant_id'],
            'zarinpal_sandbox' => $zarinpal['sandbox'],
            'zarinpal_callback_url' => $zarinpal['callback_url'],
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
                                ->default(true),
                            Forms\Components\TextInput::make('zarinpal_merchant_id')
                                ->label('Merchant ID')
                                ->placeholder('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'),
                            Forms\Components\Toggle::make('zarinpal_sandbox')
                                ->label('حالت Sandbox (تست)')
                                ->default(true),
                            Forms\Components\TextInput::make('zarinpal_callback_url')
                                ->label('آدرس Callback')
                                ->placeholder('/payment/callback')
                                ->helperText('مسیر بازگشت از درگاه پس از پرداخت'),
                        ])->columns(2),
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
