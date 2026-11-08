<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\ShopMediaPicker;
use App\Models\Category;
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
                    ->description('لینkهای کنار اسلایدر منبع اصلی نمایش هستند. اگر خالی بمانند، از فرزندان دستهٔ ریشهٔ انتخاب‌شده ساخته می‌شوند.')
                    ->schema([
                        Forms\Components\TextInput::make('hero_sidebar_title')
                            ->label('عنوان لیست کنار اسلایدر')
                            ->default('سرویس چینی')
                            ->maxLength(120),
                        Forms\Components\Repeater::make('hero_sidebar_links')
                            ->label('لینk‌های کنار اسلایدر (نام + URL)')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('نام')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('url')
                                    ->label('آدرس (URL)')
                                    ->required()
                                    ->maxLength(2048)
                                    ->columnSpanFull(),
                                ShopMediaPicker::image('image', 'product-family-sidebar-icons', 'آیکون')
                                    ->columnSpanFull(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('فعال')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->addActionLabel('افزودن لینk')
                            ->columnSpanFull(),
                        Forms\Components\Select::make('hero_sidebar_category_id')
                            ->label('دستهٔ ریشه لیست کنار اسلایدر')
                            ->options(fn () => Category::query()
                                ->whereNull('parent_id')
                                ->where('is_active', true)
                                ->orderBy('position')
                                ->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->helperText('خالی = خودکار (سرویس چینی). اگر لینک‌های بالا خالی باشند از زیردسته‌های این ریشه ساخته می‌شود.'),
                        Forms\Components\Select::make('hero_quick_category_ids')
                            ->label('سه کاشی زیر لیست (پارس اپال، …)')
                            ->options(fn () => Category::query()
                                ->where('is_active', true)
                                ->orderBy('position')
                                ->pluck('name', 'id'))
                            ->multiple()
                            ->maxItems(3)
                            ->searchable()
                            ->helperText('حداکثر ۳ مورد. لینک هر کاشی = صفحه دسته. خالی = تلاش برای slugهای پیش‌فرض.'),
                        Forms\Components\Hidden::make('hero_sidebar_family_id'),
                        Forms\Components\Hidden::make('hero_quick_family_ids'),
                        Forms\Components\Select::make('taxonomy_mode')
                            ->label('حالت taxonomy (legacy)')
                            ->options([
                                HomepageSettingsService::TAXONOMY_CATEGORIES => 'دسته‌بندی‌ها',
                                HomepageSettingsService::TAXONOMY_PRODUCT_FAMILIES => 'خانواده‌های محصول',
                            ])
                            ->helperText('دیگر در صفحه اصلی نمایش داده نمی‌شود.')
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
