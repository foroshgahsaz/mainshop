<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Filament\Support\ShopMediaPicker;
use App\Services\Settings\SearchPopupSettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageSearchPopupSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'پاپ‌آپ جستجو';

    protected static ?string $slug = 'search-popup-settings';

    protected static ?string $title = 'تنظیمات پاپ‌آپ جستجو';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 3;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(SearchPopupSettingsService $searchPopup): void
    {
        $this->form->fill($searchPopup->forAdminForm());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('جستجو')
                    ->description('متن کادر جستجو و عبارت‌های پرتکرار در پاپ‌آپ.')
                    ->schema([
                        Forms\Components\TextInput::make('placeholder')
                            ->label('متن placeholder')
                            ->maxLength(200)
                            ->columnSpanFull(),
                        Forms\Components\Repeater::make('popular_searches')
                            ->label('جستجوهای پرتکرار')
                            ->schema([
                                Forms\Components\TextInput::make('term')
                                    ->label('عبارت نمایشی')
                                    ->required()
                                    ->maxLength(80),
                                Forms\Components\TextInput::make('link')
                                    ->label('لینک (اختیاری)')
                                    ->placeholder('https://... یا /products?...')
                                    ->maxLength(500)
                                    ->helperText('خالی بماند = جستجوی همان عبارت در لیست محصولات.'),
                            ])
                            ->columns(2)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['term'] ?? 'عبارت جدید')
                            ->addActionLabel('افزودن عبارت')
                            ->defaultItems(0)
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('show_categories')
                            ->label('نمایش دسته‌بندی‌ها در پاپ‌آپ')
                            ->default(true),
                    ])
                    ->columns(1),
                Forms\Components\Section::make('بنر تبلیغاتی')
                    ->description('بنر زیر جستجوهای پرتکرار (در صورت فعال بودن).')
                    ->schema([
                        Forms\Components\Toggle::make('banner.enabled')
                            ->label('نمایش بنر')
                            ->default(true)
                            ->live(),
                        ShopMediaPicker::image('banner.image', 'search-popup', 'تصویر بنر')
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('banner.enabled')),
                        Forms\Components\TextInput::make('banner.subtitle')
                            ->label('زیرعنوان کوچک')
                            ->maxLength(80)
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('banner.enabled')),
                        Forms\Components\TextInput::make('banner.title')
                            ->label('عنوان بنر')
                            ->maxLength(120)
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('banner.enabled')),
                        Forms\Components\TextInput::make('banner.link')
                            ->label('لینک بنر')
                            ->url()
                            ->maxLength(500)
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('banner.enabled')),
                        Forms\Components\TextInput::make('banner.button_text')
                            ->label('متن دکمه')
                            ->maxLength(40)
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('banner.enabled')),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function save(SearchPopupSettingsService $searchPopup): void
    {
        $searchPopup->saveFromAdminForm($this->form->getState());

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات پاپ‌آپ جستجو ذخیره شد.')
            ->send();
    }
}
