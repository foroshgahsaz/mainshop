<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\ShopIconUpload;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageBajetPay extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'باجت‌پی';

    protected static ?string $slug = 'bajet';

    protected static ?string $title = 'تنظیمات درگاه اعتباری باجت‌پی (جت‌پی)';

    protected static ?string $navigationGroup = 'درگاه‌ها';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $bajet = $settings->bajet();

        $this->form->fill([
            'enabled' => $bajet['enabled'],
            'sandbox' => $bajet['sandbox'],
            'username' => $bajet['username'],
            'password' => $bajet['password'],
            'terminal_id' => $bajet['terminal_id'],
            'amount_unit' => $bajet['amount_unit'],
            'callback_url' => $bajet['callback_url'],
            'sandbox_base_url' => $bajet['sandbox_base_url'],
            'base_url' => $bajet['production_base_url'],
            'default_product_type' => $bajet['default_product_type'],
            'default_brand' => $bajet['default_brand'],
            'server_ip' => $bajet['server_ip'],
            'icon' => ShopIconUpload::forForm($bajet['icon'] ?? null),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('عمومی')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی باجت‌پی')
                        ->helperText('درگاه اعتباری جت‌پی (مستندات v1.4.0)'),
                    Forms\Components\Toggle::make('sandbox')
                        ->label('حالت Sandbox (تست)')
                        ->default(true),
                    Forms\Components\Select::make('amount_unit')
                        ->label('واحد مبلغ فروشگاه')
                        ->options([
                            'toman' => 'تومان (ارسال به درگاه به‌صورت ریال ×۱۰)',
                            'rial' => 'ریال (ارسال بدون تبدیل)',
                        ])
                        ->required(),
                    Forms\Components\TextInput::make('callback_url')
                        ->label('آدرس بازگشت (Return URL)')
                        ->placeholder('/payment/callback/bajet')
                        ->helperText('پس از پرداخت، کاربر به این مسیر با پارامترهای id، orderId و status برمی‌گردد'),
                    ShopIconUpload::make('icon', 'gateway-icons', 'آیکون درگاه')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('احراز هویت و API')->schema([
                    Forms\Components\TextInput::make('username')
                        ->label('نام کاربری')
                        ->required(),
                    Forms\Components\TextInput::make('password')
                        ->label('رمز عبور')
                        ->password()
                        ->revealable()
                        ->required(),
                    Forms\Components\TextInput::make('terminal_id')
                        ->label('شناسه ترمینال (terminalId)')
                        ->required(),
                    Forms\Components\TextInput::make('sandbox_base_url')
                        ->label('Base URL تست API')
                        ->placeholder('https://jetpay.mybajet.ir')
                        ->helperText('بدون اسلش انتهایی؛ پورت ۴۴۳ در HTTPS پیش‌فرض است')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('base_url')
                        ->label('Base URL عملیاتی API')
                        ->placeholder('https://jetpay.mybajet.ir')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('server_ip')
                        ->label('IP سرور فروشگاه (whitelist باجت)')
                        ->placeholder('11.9.115.162')
                        ->helperText('این IP را در پنل پذیرنده باجت ثبت کنید؛ در درخواست API ارسال نمی‌شود')
                        ->columnSpanFull(),
                ])->columns(2),
                Forms\Components\Section::make('سبد خرید (basketItems)')->schema([
                    Forms\Components\TextInput::make('default_product_type')
                        ->label('نوع محصول پیش‌فرض (productType)')
                        ->numeric()
                        ->default(2)
                        ->helperText('عدد ارسالی به API برای هر قلم؛ در صورت نبود برند روی محصول از فیلد برند پیش‌فرض استفاده می‌شود'),
                    Forms\Components\TextInput::make('default_brand')
                        ->label('برند پیش‌فرض')
                        ->default('general')
                        ->maxLength(64),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('bajet', [
            'enabled' => $data['enabled'] ?? false,
            'sandbox' => $data['sandbox'] ?? true,
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'terminal_id' => $data['terminal_id'] ?? '',
            'amount_unit' => $data['amount_unit'] ?? 'toman',
            'callback_url' => $data['callback_url'] ?? '/payment/callback/bajet',
            'sandbox_base_url' => $data['sandbox_base_url'] ?? '',
            'base_url' => $data['base_url'] ?? '',
            'default_product_type' => (string) ($data['default_product_type'] ?? '2'),
            'default_brand' => $data['default_brand'] ?? 'general',
            'server_ip' => $data['server_ip'] ?? '',
            'icon' => ShopIconUpload::fromState($data['icon'] ?? null),
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات باجت‌پی با موفقیت ذخیره شد.')
            ->send();
    }
}
