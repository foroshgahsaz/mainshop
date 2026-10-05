<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Services\Settings\SiteHeaderSettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageSiteHeaderSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationLabel = 'هدر سایت';

    protected static ?string $slug = 'site-header-settings';

    protected static ?string $title = 'هدر سایت (کدهای head)';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SiteHeaderSettingsService $siteHeaderSettings): void
    {
        $this->form->fill($siteHeaderSettings->forAdminForm());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('کدهای هدر فروشگاه')
                    ->description('اسکریپت‌ها و تگ‌های HTML که در بخش head صفحات فروشگاه قرار می‌گیرند (مثل Matomo، Google Analytics و ...). فقط از کد معتبر و از منابعی که به آن‌ها اعتماد دارید استفاده کنید.')
                    ->schema([
                        Forms\Components\Repeater::make('snippets')
                            ->label('آیتم‌ها')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('عنوان (فقط در پنل)')
                                    ->placeholder('مثلاً Matomo')
                                    ->maxLength(120),
                                Forms\Components\Toggle::make('enabled')
                                    ->label('فعال')
                                    ->default(true)
                                    ->inline(false),
                                Forms\Components\Toggle::make('defer')
                                    ->label('بارگذاری با تأخیر')
                                    ->helperText('پس از بارگذاری صفحه و در زمان بیکاری مرورگر اجرا می‌شود؛ برای آمار معمولاً قابل قبول است و به سرعت لود کمک می‌کند.')
                                    ->default(false)
                                    ->inline(false),
                                Forms\Components\Textarea::make('code')
                                    ->label('کد HTML / JavaScript')
                                    ->rows(12)
                                    ->required()
                                    ->columnSpanFull()
                                    ->helperText('می‌توانید تگ script و کامنت HTML را همان‌طور که از سرویس آمار می‌گیرید وارد کنید.'),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['label'] ?? null)
                                ? (string) $state['label']
                                : 'کد جدید')
                            ->addActionLabel('افزودن کد')
                            ->reorderable()
                            ->defaultItems(0),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(SiteHeaderSettingsService $siteHeaderSettings): void
    {
        $data = $this->form->getState();

        $siteHeaderSettings->saveFromAdminForm($data);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات هدر سایت با موفقیت ذخیره شد.')
            ->send();
    }
}
