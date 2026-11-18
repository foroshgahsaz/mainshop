<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\FileUploadSanitizer;
use App\Filament\Support\NormalizesFileUploadFormState;
use App\Filament\Support\ShopMediaPicker;
use App\Services\Media\MediaRegistry;
use App\Services\Settings\SettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageGeneralSettings extends Page implements HasForms
{
    use InteractsWithForms;
    use NormalizesFileUploadFormState {
        NormalizesFileUploadFormState::getFormUploadedFiles insteadof InteractsWithForms;
    }

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationLabel = 'تنظیمات عمومی';

    protected static ?string $title = 'تنظیمات عمومی سایت';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 0;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $site = $settings->site();

        $this->form->fill([
            'name' => $site['name'],
            'description' => $site['description'],
            'logo' => $site['logo'] ? [$site['logo']] : [],
            'favicon' => $site['favicon'] ? [$site['favicon']] : [],
            'phone' => $site['phone'],
            'email' => $site['email'],
            'address' => $site['address'],
            'instagram' => $site['instagram'],
            'telegram' => $site['telegram'],
            'maintenance_mode' => $site['maintenance_mode'],
            'maintenance_message' => $site['maintenance_message'],
            'top_banner_enabled' => $site['top_banner_enabled'] ?? false,
            'top_banner_image' => ($site['top_banner_image'] ?? null) ? [$site['top_banner_image']] : [],
            'top_banner_link' => $site['top_banner_link'] ?? '',
            'top_banner_alt' => $site['top_banner_alt'] ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('اطلاعات اصلی')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('نام سایت')
                            ->required()
                            ->maxLength(120),
                        Forms\Components\Textarea::make('description')
                            ->label('توضیح کوتاه')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('برای SEO و نمایش در فوتر'),
                        ShopMediaPicker::image('logo', 'settings', 'لوگو')->maxSize(2048),
                        ShopMediaPicker::image('favicon', 'settings', 'فاوآیکن')
                            ->maxSize(512)
                            ->helperText('فرمت PNG یا ICO، حداکثر ۵۱۲KB'),
                    ])->columns(2),
                Forms\Components\Section::make('اطلاعات تماس')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('تلفن')
                            ->tel()
                            ->placeholder('02112345678'),
                        Forms\Components\TextInput::make('email')
                            ->label('ایمیل')
                            ->email(),
                        Forms\Components\Textarea::make('address')
                            ->label('آدرس')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('شبکه‌های اجتماعی')
                    ->schema([
                        Forms\Components\TextInput::make('instagram')
                            ->label('اینستاگرام')
                            ->url()
                            ->placeholder('https://instagram.com/...'),
                        Forms\Components\TextInput::make('telegram')
                            ->label('تلگرام')
                            ->url()
                            ->placeholder('https://t.me/...'),
                    ])->columns(2),
                Forms\Components\Section::make('بنر بالای هدر')
                    ->description('نوار تمام‌عرض بالای هدر فروشگاه — تصویر یا GIF و لینک مقصد (مثلاً کمپین یا صفحهٔ فرود).')
                    ->schema([
                        Forms\Components\Toggle::make('top_banner_enabled')
                            ->label('نمایش بنر')
                            ->inline(false)
                            ->live(),
                        ShopMediaPicker::image('top_banner_image', 'settings/banners', 'تصویر بنر')
                            ->helperText('پیشنهاد: نوار افقی تمام‌عرض (مثلاً نسبت ۳۲:۱). GIF پشتیبانی می‌شود.')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('top_banner_enabled')),
                        Forms\Components\TextInput::make('top_banner_link')
                            ->label('لینک مقصد')
                            ->url()
                            ->maxLength(500)
                            ->placeholder('https://...')
                            ->helperText('با کلیک روی بنر کاربر به این آدرس می‌رود؛ خالی = بدون لینک.')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('top_banner_enabled')),
                        Forms\Components\TextInput::make('top_banner_alt')
                            ->label('متن جایگزین (دسترسی‌پذیری)')
                            ->maxLength(200)
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('top_banner_enabled')),
                    ])->columns(2),
                Forms\Components\Section::make('حالت بروزرسانی')
                    ->description('با فعال‌سازی، فقط مدیران می‌توانند فروشگاه را ببینند و سایر کاربران صفحه «در حال بروزرسانی» را مشاهده می‌کنند.')
                    ->schema([
                        Forms\Components\Toggle::make('maintenance_mode')
                            ->label('سایت در حال بروزرسانی')
                            ->inline(false)
                            ->helperText('پنل مدیریت (/admin) همیشه در دسترس مدیران است. از php artisan down استفاده نکنید — فقط این سوئیچ را فعال کنید.'),
                        Forms\Components\Textarea::make('maintenance_message')
                            ->label('پیام صفحه بروزرسانی')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(1),
            ])
            ->statePath('data');
    }

    public function save(SettingsService $settings, MediaRegistry $media): void
    {
        FileUploadSanitizer::sanitize($this, $this->form);

        $data = $this->form->getState();

        $logo = collect($data['logo'] ?? [])->first(fn ($value) => is_string($value) && $value !== '') ?? '';
        $favicon = collect($data['favicon'] ?? [])->first(fn ($value) => is_string($value) && $value !== '') ?? '';
        $topBannerImage = collect($data['top_banner_image'] ?? [])->first(fn ($value) => is_string($value) && $value !== '') ?? '';

        $settings->setMany('site', [
            'name' => $data['name'] ?? '',
            'description' => $data['description'] ?? '',
            'logo' => $logo,
            'favicon' => $favicon,
            'phone' => $data['phone'] ?? '',
            'email' => $data['email'] ?? '',
            'address' => $data['address'] ?? '',
            'instagram' => $data['instagram'] ?? '',
            'telegram' => $data['telegram'] ?? '',
            'maintenance_mode' => (bool) ($data['maintenance_mode'] ?? false),
            'maintenance_message' => $data['maintenance_message'] ?? '',
            'top_banner_enabled' => (bool) ($data['top_banner_enabled'] ?? false),
            'top_banner_image' => $topBannerImage,
            'top_banner_link' => trim((string) ($data['top_banner_link'] ?? '')),
            'top_banner_alt' => trim((string) ($data['top_banner_alt'] ?? '')),
        ]);

        foreach (array_filter([
            'logo' => $logo,
            'favicon' => $favicon,
            'top_banner_image' => $topBannerImage,
        ]) as $path) {
            $media->registerFromPath('public', $path);
        }

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات سایت با موفقیت ذخیره شد.')
            ->send();
    }
}
