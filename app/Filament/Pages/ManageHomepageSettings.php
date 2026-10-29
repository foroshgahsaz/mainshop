<?php

namespace App\Filament\Pages;

use App\Filament\Resources\ProductFamilyResource;
use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\ShopMediaPicker;
use App\Models\ProductFamily;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use Filament\Forms;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;
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
                    ->description('لینkهای کنار اسلایدر را اینجا مستقیم وارد کنید (منبع اصلی نمایش در سایت). با ذخیره، در صورت خالی بودن از خانوade محصول هم پر می‌شود.')
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
                        Forms\Components\Select::make('hero_sidebar_family_id')
                            ->label('خانواده لیست کنار اسلایدر')
                            ->options(fn () => ProductFamily::query()->where('is_active', true)->orderBy('position')->pluck('name', 'id'))
                            ->searchable()
                            ->nullable()
                            ->live()
                            ->helperText('خالی = خانواده با slug «chinese-service».'),
                        Forms\Components\Placeholder::make('hero_sidebar_links_help')
                            ->label('مدیریت لینک‌ها (والاس، مارشال، …)')
                            ->content(function (Get $get): HtmlString|string {
                                $id = (int) $get('hero_sidebar_family_id');
                                if ($id <= 0) {
                                    $id = (int) (ProductFamily::query()
                                        ->where('slug', 'chinese-service')
                                        ->value('id') ?? 0);
                                }

                                $candidates = ProductFamily::query()
                                    ->where('is_active', true)
                                    ->where(function ($query) {
                                        $query->where('slug', 'chinese-service')
                                            ->orWhere('name', 'سرویس چینی');
                                    })
                                    ->orderBy('position')
                                    ->get();

                                if ($candidates->isEmpty()) {
                                    return 'ابتدا خانواده «سرویس چینی» را در **فروشگاه → خانواده محصول** بسازید (slug: chinese-service).';
                                }

                                $lines = [];
                                foreach ($candidates as $family) {
                                    $activeCount = $family->homepagePopupItems()->where('is_active', true)->count();
                                    $editUrl = ProductFamilyResource::getUrl('edit', ['record' => $family->id]);
                                    $lines[] = '<li><a href="'.e($editUrl).'" target="_blank" rel="noopener" class="font-bold text-primary-600 underline">'
                                        .e($family->name).' (#'.$family->id.', slug: '.e($family->slug).')</a>'
                                        .' — <span class="text-gray-500">'.$activeCount.' لینک فعال</span></li>';
                                }

                                $selected = $id > 0 ? ProductFamily::query()->find($id) : null;

                                return new HtmlString(
                                    '<p class="text-sm leading-6 text-gray-600 dark:text-gray-400">'
                                    .'لینک‌ها در ویرایش خانواده → «لینک‌های کنار اسلایدر». '
                                    .($selected ? 'انتخاب فعلی: <strong>#'.e((string) $selected->id).'</strong>.' : 'انتخاب فعلی: <strong>خودکار (chinese-service)</strong>.')
                                    .'</p><ul class="mt-2 list-disc pr-5 text-sm space-y-1">'.implode('', $lines).'</ul>'
                                );
                            })
                            ->columnSpanFull(),
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
