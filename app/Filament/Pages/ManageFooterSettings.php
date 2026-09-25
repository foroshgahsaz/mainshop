<?php

namespace App\Filament\Pages;

use App\Filament\Support\CrudSuccessNotification;
use App\Services\Settings\FooterSettingsService;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;

class ManageFooterSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'فوتر سایت';

    protected static ?string $slug = 'footer-settings';

    protected static ?string $title = 'تنظیمات فوتر';

    protected static ?string $navigationGroup = 'تنظیمات';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.manage-general-settings';

    public ?array $data = [];

    public function mount(FooterSettingsService $footerSettings): void
    {
        $this->form->fill($footerSettings->forAdminForm());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('ویژگی‌های بالای فوتر')
                    ->description('چهار باکس آبی رنگ بالای فوتر (ارسال سریع، ضمانت اصالت و ...).')
                    ->schema([
                        Forms\Components\Repeater::make('features')
                            ->label('ویژگی‌ها')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('عنوان')
                                    ->required()
                                    ->maxLength(80),
                                Forms\Components\TextInput::make('subtitle')
                                    ->label('توضیح کوتاه')
                                    ->maxLength(120),
                                Forms\Components\Toggle::make('enabled')
                                    ->label('فعال')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'ویژگی جدید')
                            ->addActionLabel('افزودن ویژگی')
                            ->reorderable()
                            ->defaultItems(0),
                    ]),
                Forms\Components\Section::make('اطلاعات برند و تماس')
                    ->schema([
                        Forms\Components\Textarea::make('brand_description')
                            ->label('توضیح کوتاه برند')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('phone')
                            ->label('تلفن')
                            ->placeholder('021-9100-1234')
                            ->maxLength(30),
                        Forms\Components\TextInput::make('mobile')
                            ->label('موبایل')
                            ->placeholder('0912-000-1234')
                            ->maxLength(30),
                        Forms\Components\Textarea::make('address')
                            ->label('آدرس')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),
                    ])->columns(2),
                Forms\Components\Section::make('شبکه‌های اجتماعی')
                    ->description('اگر لینک خالی باشد، آیکن مربوطه در فوتر نمایش داده نمی‌شود.')
                    ->schema([
                        Forms\Components\TextInput::make('instagram')
                            ->label('اینستاگرام')
                            ->url()
                            ->placeholder('https://instagram.com/...'),
                        Forms\Components\TextInput::make('telegram')
                            ->label('تلگرام')
                            ->url()
                            ->placeholder('https://t.me/...'),
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('واتساپ')
                            ->url()
                            ->placeholder('https://wa.me/...'),
                        Forms\Components\TextInput::make('linkedin')
                            ->label('لینکدین')
                            ->url()
                            ->placeholder('https://linkedin.com/...'),
                    ])->columns(2),
                Forms\Components\Section::make('دسترسی سریع')
                    ->schema([
                        Forms\Components\TextInput::make('quick_links_title')
                            ->label('عنوان ستون')
                            ->default('دسترسی سریع')
                            ->maxLength(80),
                        Forms\Components\Repeater::make('quick_links')
                            ->label('لینک‌ها')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('عنوان')
                                    ->required()
                                    ->maxLength(120),
                                Forms\Components\TextInput::make('url')
                                    ->label('آدرس')
                                    ->required()
                                    ->maxLength(500)
                                    ->helperText('می‌توانید آدرس کامل یا مسیر داخلی مثل /products وارد کنید.'),
                                Forms\Components\Toggle::make('enabled')
                                    ->label('فعال')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'لینک جدید')
                            ->addActionLabel('افزودن لینک')
                            ->reorderable()
                            ->defaultItems(0),
                    ]),
                Forms\Components\Section::make('متن درباره فروشگاه')
                    ->description('پاراگراف‌های پایین بخش اصلی فوتر.')
                    ->schema([
                        Forms\Components\Repeater::make('about_paragraphs')
                            ->label('پاراگراف‌ها')
                            ->schema([
                                Forms\Components\Textarea::make('text')
                                    ->label('متن')
                                    ->rows(2)
                                    ->required()
                                    ->maxLength(500)
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => filled($state['text'] ?? null)
                                ? \Illuminate\Support\Str::limit($state['text'], 40)
                                : 'پاراگراف جدید')
                            ->addActionLabel('افزودن پاراگراف')
                            ->reorderable()
                            ->defaultItems(0),
                    ]),
                Forms\Components\Section::make('نوار پایین فوتر')
                    ->schema([
                        Forms\Components\TextInput::make('copyright')
                            ->label('متن کپی‌رایت')
                            ->maxLength(200)
                            ->columnSpanFull()
                            ->helperText('اگر خالی بماند، متن پیش‌فرض با نام سایت نمایش داده می‌شود.'),
                        Forms\Components\Repeater::make('bottom_links')
                            ->label('لینک‌های پایین')
                            ->schema([
                                Forms\Components\TextInput::make('label')
                                    ->label('عنوان')
                                    ->required()
                                    ->maxLength(80),
                                Forms\Components\TextInput::make('url')
                                    ->label('آدرس')
                                    ->required()
                                    ->maxLength(500),
                                Forms\Components\Toggle::make('enabled')
                                    ->label('فعال')
                                    ->default(true)
                                    ->inline(false),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'لینک جدید')
                            ->addActionLabel('افزودن لینک')
                            ->reorderable()
                            ->defaultItems(0),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(FooterSettingsService $footerSettings): void
    {
        $data = $this->form->getState();

        $footerSettings->saveFromAdminForm($data);

        CrudSuccessNotification::saved()
            ->title('ذخیره شد')
            ->body('تنظیمات فوتر با موفقیت ذخیره شد.')
            ->send();
    }
}
