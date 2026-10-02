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
            'staff_phones' => $settings->staffPhonesRaw(),
            'templates' => $settings->forAdminForm(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('هشدار پرسنل (اختیاری)')
                    ->description('برای پیامک‌های «هشدار پرسنل» شماره‌ها را با کاما یا خط جدید وارد کنید. الگوهای staff_new_order و staff_new_proforma را در پایین فعال کنید.')
                    ->schema([
                        Forms\Components\Textarea::make('staff_phones')
                            ->label('شماره موبایل پرسنل')
                            ->rows(2)
                            ->placeholder('09121234567, 09131112233')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->compact(),
                Forms\Components\Section::make('قالب پیامک‌ها')
                    ->description('برای ارسال، sms.ir یا کاوه‌نگار در تنظیمات پیامک فعال باشد. متغیرها: {order_code}، {amount}، …')
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
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                            ])
                            ->collapsible()
                            ->collapsed(fn (Forms\Get $get): bool => ! ($get('enabled') ?? true))
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
        $state = $this->form->getState();

        $settings->saveFromAdminForm(
            $state['templates'] ?? [],
            (string) ($state['staff_phones'] ?? ''),
        );

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('متن پیامک‌های تراکنشی ذخیره شد.')
            ->send();
    }

    protected function placeholderHelp(string $key): string
    {
        return match ($key) {
            'account_created' => '{site_name}، {name}، {phone}',
            'order_placed', 'order_canceled', 'order_expired_unpaid' => '{site_name}، {name}، {phone}، {order_code}، {amount}، {payment_method}، {items}، {items_count}',
            'order_paid' => '{site_name}، {order_code}، {paid_amount}، {gateway}، {payment_tracking}، {items}',
            'payment_failed', 'payment_partial_remaining' => '{site_name}، {order_code}، {amount}، {remaining_amount}',
            'order_shipped' => '{site_name}، {order_code}، {tracking_suffix} (مثلاً « رهگیری: 123»)',
            'order_delivered' => '{site_name}، {order_code}',
            'proforma_created', 'proforma_reservation_expired', 'proforma_reservation_extended' => '{site_name}، {order_code}، {amount}، {reserved_until}',
            'staff_new_order' => '{site_name}، {order_code}، {amount}، {phone}',
            'staff_new_proforma' => '{site_name}، {order_code}، {amount}، {representative_name}',
            default => 'متغیرهای رایج: {site_name}، {order_code}، {amount}، {phone}',
        };
    }
}
