<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\ShopMediaPicker;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageHomepageSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'صفحه اصلی فروشگاه';

    protected static ?string $slug = 'homepage-settings';

    protected static ?string $title = 'تنظیمات صفحه اصلی فروشگاه';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(HomepageSettingsService $homepage): void
    {
        $this->form->fill($homepage->forAdminForm());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('بنر بالای صفحه (اسلایدر تمام‌عرض)')
                    ->description('اسلایدرها از منوی «اسلایدرهای صفحه اصلی» مدیریت می‌شوند. اندازه پیشنهادی دسکتاپ: 2560×597 px (نسبت بنر تمام‌عرض).')
                    ->schema([
                        Forms\Components\Placeholder::make('hero_slider_hint')
                            ->label('')
                            ->content('سایدبار کنار اسلایدر حذف شده است. دسته‌بندی از مگا منو و صفحه دسته‌ها در دسترس است.'),
                        Forms\Components\Hidden::make('taxonomy_mode')
                            ->default(HomepageSettingsService::TAXONOMY_CATEGORIES),
                    ]),
                Forms\Components\Section::make('جدیدترین محصولات')
                    ->schema([
                        Forms\Components\Toggle::make('new_products_enabled')
                            ->label('نمایش بخش جدیدترین محصولات')
                            ->default(true)
                            ->live(),
                        Forms\Components\Textarea::make('new_product_ids')
                            ->label('شناسه محصولات (اختیاری)')
                            ->rows(3)
                            ->placeholder('مثال: 12, 45, 78')
                            ->helperText('با کاما یا خط جدید جدا کنید. ترتیب نمایش همان ترتیب وارد شده است. خالی = ۸ محصول آخر به‌صورت خودکار.')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('new_products_enabled'))
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(HomepageSettingsService $homepage, ShopCacheService $cache): void
    {
        $homepage->saveFromAdminForm($this->form->getState());
        $cache->forgetHome();

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات صفحه اصلی ذخیره شد.')
            ->send();
    }
}
