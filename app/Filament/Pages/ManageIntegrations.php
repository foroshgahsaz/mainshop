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

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'کاوه‌نگار';

    protected static ?string $slug = 'kavenegar';

    protected static ?string $title = 'تنظیمات پیامک کاوه‌نگار';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $kavenegar = $settings->kavenegar();

        $this->form->fill([
            'enabled' => $kavenegar['enabled'],
            'api_key' => $kavenegar['api_key'],
            'sender' => $kavenegar['sender'],
            'otp_template' => $kavenegar['otp_template'],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('کاوه‌نگار')->schema([
                    Forms\Components\Toggle::make('enabled')
                        ->label('فعال‌سازی کاوه‌نگار')
                        ->helperText('برای OTP ورود کاربران'),
                    Forms\Components\TextInput::make('api_key')
                        ->label('API Key')
                        ->password()
                        ->revealable(),
                    Forms\Components\TextInput::make('sender')
                        ->label('شماره خط (Sender)')
                        ->placeholder('10004346'),
                    Forms\Components\TextInput::make('otp_template')
                        ->label('نام قالب OTP (Lookup)')
                        ->placeholder('verify')
                        ->helperText('نام قالبی که در پنل کاوه‌نگار ساختید'),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings): void
    {
        $data = $this->form->getState();

        $settings->setMany('kavenegar', [
            'enabled' => $data['enabled'] ?? false,
            'api_key' => $data['api_key'] ?? '',
            'sender' => $data['sender'] ?? '',
            'otp_template' => $data['otp_template'] ?? 'verify',
        ]);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات کاوه‌نگار با موفقیت ذخیره شد.')
            ->send();
    }
}
