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
            'top_banner_image_desktop' => ($site['top_banner_image_desktop'] ?? null) ? [$site['top_banner_image_desktop']] : [],
            'top_banner_image_mobile' => ($site['top_banner_image_mobile'] ?? null) ? [$site['top_banner_image_mobile']] : [],
            'top_banner_link' => $site['top_banner_link'] ?? '',
            'top_banner_link_mobile' => $site['top_banner_link_mobile'] ?? '',
            'top_banner_alt' => $site['top_banner_alt'] ?? '',
            'top_banner_alt_mobile' => $site['top_banner_alt_mobile'] ?? '',
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
                    ->description('نوار تمام‌عرض بالای هدر — برای دسکتاپ و موبایل می‌توانید تصویر جدا (مثلاً GIF باریک موبایل و نوار بلند دسکتاپ) و لینک جدا بگذارید.')
                    ->schema([
                        Forms\Components\Toggle::make('top_banner_enabled')
                            ->label('نمایش بنر')
                            ->inline(false)
                            ->live(),
                        Forms\Components\Fieldset::make('دسکتاپ (عرض ۷۶۸px به بالا)')
                            ->schema([
                                ShopMediaPicker::image('top_banner_image_desktop', 'settings/banners', 'تصویر بنر دسکتاپ')
                                    ->helperText('نوار افقی تمام‌عرض؛ GIF پشتیبانی می‌شود.')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('top_banner_link')
                                    ->label('لینک مقصد')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('https://...'),
                                Forms\Components\TextInput::make('top_banner_alt')
                                    ->label('متن جایگزین (alt)')
                                    ->maxLength(200),
                            ])
                            ->columns(2)
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('top_banner_enabled')),
                        Forms\Components\Fieldset::make('موبایل (عرض کمتر از ۷۶۸px)')
                            ->schema([
                                ShopMediaPicker::image('top_banner_image_mobile', 'settings/banners', 'تصویر بنر موبایل')
                                    ->helperText('اختیاری — اگر خالی باشد همان تصویر دسکتاپ نمایش داده می‌شود.')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('top_banner_link_mobile')
                                    ->label('لینک مقصد موبایل')
                                    ->url()
                                    ->maxLength(500)
                                    ->placeholder('https://...')
                                    ->helperText('خالی = همان لینک دسکتاپ.'),
                                Forms\Components\TextInput::make('top_banner_alt_mobile')
                                    ->label('متن جایگزین موبایل (alt)')
                                    ->maxLength(200)
                                    ->helperText('خالی = همان alt دسکتاپ.'),
                            ])
                            ->columns(2)
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('top_banner_enabled')),
                    ]),
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
        $topBannerDesktop = collect($data['top_banner_image_desktop'] ?? [])->first(fn ($value) => is_string($value) && $value !== '') ?? '';
        $topBannerMobile = collect($data['top_banner_image_mobile'] ?? [])->first(fn ($value) => is_string($value) && $value !== '') ?? '';

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
            'top_banner_image' => $topBannerDesktop,
            'top_banner_image_desktop' => $topBannerDesktop,
            'top_banner_image_mobile' => $topBannerMobile,
            'top_banner_link' => trim((string) ($data['top_banner_link'] ?? '')),
            'top_banner_link_mobile' => trim((string) ($data['top_banner_link_mobile'] ?? '')),
            'top_banner_alt' => trim((string) ($data['top_banner_alt'] ?? '')),
            'top_banner_alt_mobile' => trim((string) ($data['top_banner_alt_mobile'] ?? '')),
        ]);

        foreach (array_filter([
            'logo' => $logo,
            'favicon' => $favicon,
            'top_banner_image_desktop' => $topBannerDesktop,
            'top_banner_image_mobile' => $topBannerMobile,
        ]) as $path) {
            $media->registerFromPath('public', $path);
        }

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات سایت با موفقیت ذخیره شد.')
            ->send();
    }
}
