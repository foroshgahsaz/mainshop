<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\GatewayCheckoutDisplayFields;
use App\Filament\Support\ShopIconUpload;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;

class ManageSnappPay extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'اسنپ‌پی';

    protected static ?string $slug = 'snapppay';

    protected static ?string $title = 'تنظیمات درگاه اعتباری اسنپ‌پی';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $snapppay = $settings->snapppay();

        $this->form->fill([
            'enabled' => $snapppay['enabled'],
            'amount_unit' => $snapppay['amount_unit'],
            'callback_url' => $snapppay['callback_url'],
            'base_url' => $snapppay['base_url'],
            'client_id' => $snapppay['client_id'],
            'client_secret' => $snapppay['client_secret'],
            'client_username' => $snapppay['client_username'],
            'client_password' => $snapppay['client_password'],
            'icon' => ShopIconUpload::forForm($snapppay['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::mountState($snapppay),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('عمومی')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی اسنپ‌پی')
                        ->helperText('پرداخت اقساطی اسنپ‌پی (INSTALLMENT) — پروداکشن api.snapppay.ir'),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به API به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('callback_url')
                        ->label('آدرس بازگشت (Callback)')
                        ->placeholder('/payment/callback/snapppay')
                        ->helperText('بازگشت با POST و پارامتر state؛ شناسه payment در query است.')
                        ->columnSpanFull(),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون درگاه')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('حساب اسنپ‌پی')->schema([
                    Forms\Components\TextInput::make('base_url')
                        ->label('آدرس API')
                        ->placeholder('https://api.snapppay.ir')
                        ->required()
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('client_id')
                        ->label('شماره مشتری (client_id)')
                        ->required(),
                    Forms\Components\TextInput::make('client_secret')
                        ->label('کد امنیتی مشتری (client_secret)')
                        ->password()
                        ->revealable()
                        ->required(),
                    Forms\Components\TextInput::make('client_username')
                        ->label('نام کاربری مشتری')
                        ->required(),
                    Forms\Components\TextInput::make('client_password')
                        ->label('رمز عبور مشتری')
                        ->password()
                        ->revealable()
                        ->required(),
                ])->columns(2),
                GatewayCheckoutDisplayFields::section(),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('snapppay', [
            'enabled' => $data['enabled'] ?? false,
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'callback_url' => $data['callback_url'] ?? '/payment/callback/snapppay',
            'base_url' => rtrim(trim((string) ($data['base_url'] ?? '')), '/'),
            'client_id' => trim((string) ($data['client_id'] ?? '')),
            'client_secret' => trim((string) ($data['client_secret'] ?? '')),
            'client_username' => trim((string) ($data['client_username'] ?? '')),
            'client_password' => trim((string) ($data['client_password'] ?? '')),
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
            ...GatewayCheckoutDisplayFields::saveState($data),
        ]);

        $saved = $settings->snapppay();
        Cache::forget('snapppay:bearer:'.md5((string) ($saved['client_id'] ?? '').'|'.($saved['base_url'] ?? '')));

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات اسنپ‌پی ذخیره شد. توکن OAuth کش پاک شد.')
            ->send();
    }
}