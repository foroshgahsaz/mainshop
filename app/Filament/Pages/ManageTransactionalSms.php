<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Services\Settings\TransactionalSmsSettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageTransactionalSms extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $navigationLabel = 'متن پیامک‌های تراکنشی';

    protected static ?string $slug = 'transactional-sms';

    protected static ?string $title = 'متن پیامک‌های تراکنشی';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(TransactionalSmsSettingsService $settings): void
    {
        $this->form->fill([
            'templates' => $settings->forAdminForm(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('قالب پیامک‌ها')
                    ->description('برای ارسال، سامانه sms.ir (خط اختصاصی) یا کاوه‌نگار باید در تنظیمات پیامک فعال باشد. متغیرها را داخل آکولاد بنویسید، مثلاً {order_code}.')
                    ->schema([
                        Forms\Components\Repeater::make('templates')
                            ->label('رویدادها')
                            ->schema([
                                Forms\Components\Hidden::make('key'),
                                Forms\Components\TextInput::make('label')
                                    ->label('رویداد')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Toggle::make('enabled')
                                    ->label('فعال')
                                    ->default(true)
                                    ->inline(false),
                                Forms\Components\Textarea::make('body')
                                    ->label('متن پیامک')
                                    ->rows(4)
                                    ->required()
                                    ->columnSpanFull()
                                    ->helperText(fn (Forms\Get $get): string => $this->placeholderHelp((string) $get('key'))),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->reorderable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(TransactionalSmsSettingsService $settings): void
    {
        $settings->saveFromAdminForm($this->form->getState()['templates'] ?? []);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('متن پیامک‌های تراکنشی ذخیره شد.')
            ->send();
    }

    protected function placeholderHelp(string $key): string
    {
        return match ($key) {
            'account_created' => 'متغیرها: {site_name}، {name}، {phone}',
            'order_placed' => 'متغیرها: {site_name}، {name}، {phone}، {order_code}، {amount}، {payment_method}، {items}، {items_count}',
            'order_paid' => 'متغیرها: {site_name}، {name}، {phone}، {order_code}، {amount}، {paid_amount}، {gateway}، {payment_method}، {payment_tracking}، {items}، {items_count}',
            default => '',
        };
    }
}
