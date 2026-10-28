<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Models\ProductFamily;
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
                Forms\Components\Section::make('باکس بالای صفحه (اسلایدر + سایدبار)')
                    ->description('لیست سمت راست اسلایدر = لینک‌های پاپ‌آپ خانواده انتخاب‌شده (مثل سرویس چینی). سه خانواده پایین = میانبر دایره‌ای.')
                    ->schema([
                        Forms\Components\Select::make('hero_sidebar_family_id')
                            ->label('خانواده لیست کنار اسلایدر')
                            ->options(fn () => ProductFamily::query()->where('is_active', true)->orderBy('position')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('خالی = خانواده با slug «chinese-service». لینک‌ها از «پاپ‌آپ صفحه اصلی» همان خانواده.'),
                        Forms\Components\Select::make('hero_quick_family_ids')
                            ->label('سه خانواده زیر لیست (پارس اپال، …)')
                            ->options(fn () => ProductFamily::query()->where('is_active', true)->orderBy('position')->pluck('name', 'id'))
                            ->multiple()
                            ->maxItems(3)
                            ->searchable()
                            ->helperText('حداکثر ۳ مورد. خالی = تلاش برای slugهای pars-opal، kitchenware، crystal-service.'),
                        Forms\Components\Select::make('taxonomy_mode')
                            ->label('حالت taxonomy (legacy)')
                            ->options([
                                HomepageSettingsService::TAXONOMY_CATEGORIES => 'دسته‌بندی‌ها',
                                HomepageSettingsService::TAXONOMY_PRODUCT_FAMILIES => 'خانواده‌های محصول',
                            ])
                            ->helperText('دیگر در صفحه اصلی نمایش داده نمی‌شود؛ فقط برای سازگاری تنظیمات.')
                            ->required(),
                    ])->columns(1),
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
