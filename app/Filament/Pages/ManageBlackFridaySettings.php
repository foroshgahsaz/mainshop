<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\ShopMediaPicker;
use App\Services\Settings\BlackFridaySettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageBlackFridaySettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'بلک فرایدی';

    protected static ?string $slug = 'blackfriday-settings';

    protected static ?string $title = 'مدیریت بلک فرایدی';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(BlackFridaySettingsService $blackFriday): void
    {
        $this->form->fill($blackFriday->forAdminForm());
    }

    public function form(Form $form): Form
    {
        $promoSlots = [];

        for ($i = 1; $i <= BlackFridaySettingsService::PROMO_SLOT_COUNT; $i++) {
            $promoSlots[] = Forms\Components\Section::make("بنر {$i}")
                ->schema([
                    Forms\Components\TextInput::make("promo_slot_{$i}_label")
                        ->label('عنوان (aria / سئو)')
                        ->required()
                        ->maxLength(160),
                    ShopMediaPicker::make("promo_slot_{$i}_image")
                        ->label('تصویر بنر')
                        ->directory('campaigns/blackfriday')
                        ->maxSize(51200),
                    Forms\Components\TextInput::make("promo_slot_{$i}_url")
                        ->label('لینک مقصد')
                        ->required()
                        ->maxLength(500)
                        ->placeholder('https://chinibazar.ir/...'),
                ])
                ->columns(1)
                ->collapsible();
        }

        return $form
            ->schema([
                Forms\Components\Section::make('وضعیت و لینک')
                    ->schema([
                        Forms\Components\Toggle::make('enabled')
                            ->label('صفحه فعال باشد')
                            ->default(true)
                            ->inline(false),
                        Forms\Components\Placeholder::make('public_url')
                            ->label('آدرس عمومی')
                            ->content(route('campaigns.blackfriday')),
                    ]),
                Forms\Components\Section::make('هیرو و متن‌ها')
                    ->schema([
                        Forms\Components\TextInput::make('badge_text')
                            ->label('برچسب بالای هیرو')
                            ->maxLength(120),
                        ShopMediaPicker::image('header_logo', 'campaigns/blackfriday', 'لوگوی هدر')
                            ->columnSpanFull(),
                        ShopMediaPicker::image('hero_logo', 'campaigns/blackfriday', 'لوگوی بزرگ هیرو')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('hero_intro')
                            ->label('متن زیر لوگو')
                            ->rows(3)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('cta_label')
                            ->label('متن دکمه مشاهده تخفیف')
                            ->maxLength(80),
                        Forms\Components\TextInput::make('section_title')
                            ->label('عنوان بخش بنرها')
                            ->maxLength(120),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('منوی بالا')
                    ->schema([
                        Forms\Components\Repeater::make('nav_links')
                            ->label('آیتم‌های منو')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('عنوان')
                                    ->required()
                                    ->maxLength(80),
                                Forms\Components\TextInput::make('url')
                                    ->label('لینک')
                                    ->required()
                                    ->maxLength(500)
                                    ->placeholder('#products یا https://...')
                                    ->helperText('برای اسکرول داخل صفحه از #products یا #store استفاده کنید.'),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('افزودن لینک منو'),
                    ]),
                Forms\Components\Section::make('بنرهای تخفیف (پایین صفحه)')
                    ->description('پنج بنر ثابت — هر تصویر روی همان بنر ذخیره می‌شود (بدون تداخل Repeater و MediaPicker).')
                    ->schema($promoSlots),
                Forms\Components\Section::make('نوار متحرک و فوتر')
                    ->schema([
                        Forms\Components\Repeater::make('marquee_items')
                            ->label('عبارت‌های نوار متحرک')
                            ->schema([
                                Forms\Components\TextInput::make('text')
                                    ->label('متن')
                                    ->required()
                                    ->maxLength(120),
                            ])
                            ->reorderable()
                            ->addActionLabel('افزودن عبارت')
                            ->defaultItems(4),
                        Forms\Components\Textarea::make('footer_text')
                            ->label('متن فوتر')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(BlackFridaySettingsService $blackFriday): void
    {
        $data = $this->form->getState();
        $blackFriday->saveFromAdminForm($data);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات صفحه بلک فرایدی به‌روز شد.')
            ->send();
    }
}