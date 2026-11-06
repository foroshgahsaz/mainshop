<?php

namespace App\Filament\Support;

use Filament\Forms;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\ViewField;

class SeoFormSchema
{
    /** @return array<int, Forms\Components\Component> */
    public static function productSection(): array
    {
        return [
            self::yoastSection(primaryTitleKey: 'name', contentKey: 'description', shortKey: 'short_description'),
        ];
    }

    /**
     * @param  'title'|'name'  $primaryTitleKey
     */
    public static function contentSection(
        string $primaryTitleKey = 'title',
        string $contentKey = 'content',
        ?string $shortKey = null,
    ): array {
        return [
            self::yoastSection(primaryTitleKey: $primaryTitleKey, contentKey: $contentKey, shortKey: $shortKey),
        ];
    }

    /**
     * @param  'title'|'name'  $primaryTitleKey
     */
    protected static function yoastSection(
        string $primaryTitleKey,
        string $contentKey,
        ?string $shortKey,
    ): Forms\Components\Section {
        return Forms\Components\Section::make('سئو')
            ->description('چیدمان شبیه Yoast — تب‌ها: تحلیل سئو، نمایش در گوگل، شبکه‌های اجتماعی، پیشرفته.')
            ->icon('heroicon-o-magnifying-glass')
            ->schema([
                Tabs::make('seo_yoast_tabs')
                    ->tabs([
                        Tabs\Tab::make('تحلیل سئو')
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                Forms\Components\TextInput::make('focus_keyword')
                                    ->label('کلمهٔ کلیدی تمرکز')
                                    ->maxLength(120)
                                    ->columnSpanFull()
                                    ->live(debounce: 500)
                                    ->helperText('۲–۴ واژهٔ اصلی این صفحه. چراغ‌ها و تحلیل از روی همین عبارت ساخته می‌شود؛ بدون AI.'),
                                self::analysisView($primaryTitleKey, $contentKey, $shortKey),
                            ]),
                        Tabs\Tab::make('نمایش در گوگل')
                            ->icon('heroicon-m-globe-alt')
                            ->schema([
                                ...self::snippetFields(),
                                ViewField::make('_seo_google_preview')
                                    ->view('filament.components.seo-google-preview')
                                    ->viewData([
                                        'primary_title_key' => $primaryTitleKey,
                                    ])
                                    ->dehydrated(false)
                                    ->columnSpanFull(),
                            ]),
                        Tabs\Tab::make('شبکه‌های اجتماعی')
                            ->icon('heroicon-m-share')
                            ->schema([
                                ...self::socialFields(),
                            ]),
                        Tabs\Tab::make('پیشرفته')
                            ->icon('heroicon-m-cog-6-tooth')
                            ->schema([
                                ...self::technicalFields(),
                            ]),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(false),
            ])
            ->columns(1)
            ->collapsible();
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function snippetFields(): array
    {
        return [
            Forms\Components\TextInput::make('meta_title')
                ->label('عنوان متا (SEO title)')
                ->maxLength(70)
                ->helperText('حدود ۴۰–۷۰ کاراکتر؛ اگر خالی باشد از عنوان اصلی استفاده می‌شود.')
                ->live(debounce: 600),
            Forms\Components\Textarea::make('meta_description')
                ->label('توضیح متا (Meta description)')
                ->maxLength(160)
                ->rows(3)
                ->helperText('حدود ۱۲۰–۱۶۰ کاراکتر برای snippet گوگل.')
                ->live(debounce: 600),
            Forms\Components\TextInput::make('meta_keywords')
                ->label('کلمات کلیدی (اختیاری)')
                ->maxLength(255)
                ->helperText('با ویرگول جدا کنید؛ نقش مستقیم محدود است.')
                ->live(debounce: 750),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function socialFields(): array
    {
        return [
            Forms\Components\TextInput::make('og_title')
                ->label('عنوان برای فیسبوک')
                ->maxLength(70)
                ->helperText('اگر خالی باشد معمولاً از عنوان متا استفاده می‌شود.')
                ->live(debounce: 600),
            Forms\Components\Textarea::make('og_description')
                ->label('توضیح برای فیسبوک')
                ->maxLength(200)
                ->rows(2)
                ->live(debounce: 600),
            ShopMediaPicker::image('og_image', 'seo', 'تصویر شبکه‌های اجتماعی (Open Graph)')->columnSpanFull(),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function technicalFields(): array
    {
        return [
            Forms\Components\TextInput::make('canonical_url')
                ->label('آدرس Canonical')
                ->url()
                ->helperText('اختیاری.')
                ->live(debounce: 600),
            Forms\Components\Select::make('robots')
                ->label('دستور Robots')
                ->options([
                    'index,follow' => 'Index, Follow',
                    'index,nofollow' => 'Index, NoFollow',
                    'noindex,follow' => 'NoIndex, Follow',
                    'noindex,nofollow' => 'NoIndex, NoFollow',
                ])
                ->default('index,follow')
                ->live()
                ->dehydrateStateUsing(fn (?string $state): string => filled($state) ? $state : 'index,follow'),
        ];
    }

    protected static function analysisView(string $primaryTitleKey, string $contentKey, ?string $shortKey): ViewField
    {
        $viewData = [
            'primary_title_key' => $primaryTitleKey,
            'content_key' => $contentKey,
            'short_key' => $shortKey,
        ];

        return ViewField::make('_seo_content_analysis')
            ->view('filament.components.seo-analysis-panel')
            ->viewData($viewData)
            ->dehydrated(false)
            ->columnSpanFull();
    }
}
