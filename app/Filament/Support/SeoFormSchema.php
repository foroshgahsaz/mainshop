<?php

namespace App\Filament\Support;

use Filament\Forms;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Get;

class SeoFormSchema
{
    /** @return array<int, Forms\Components\Component> */
    public static function productSection(): array
    {
        return [
            Forms\Components\Section::make('SEO و شبکه‌های اجتماعی')
                ->icon('heroicon-o-magnifying-glass')
                ->schema([
                    ...self::baseFields(includeOgText: true),
                    self::analysisView('name', 'description', 'short_description'),
                ])
                ->columns(2)
                ->collapsible(),
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
            Forms\Components\Section::make('SEO و شبکه‌های اجتماعی')
                ->icon('heroicon-o-magnifying-glass')
                ->schema([
                    ...self::baseFields(includeOgText: false),
                    self::analysisView($primaryTitleKey, $contentKey, $shortKey),
                ])
                ->columns(2)
                ->collapsible(),
        ];
    }

    /** @return array<int, Forms\Components\Component> */
    protected static function baseFields(bool $includeOgText): array
    {
        $fields = [
            Forms\Components\TextInput::make('meta_title')
                ->label('عنوان متا (Title Tag)')
                ->maxLength(70)
                ->helperText('حداکثر ۶۰–۷۰ کاراکتر'),
            Forms\Components\Textarea::make('meta_description')
                ->label('توضیحات متا')
                ->maxLength(160)
                ->rows(3),
            Forms\Components\TextInput::make('meta_keywords')
                ->label('کلمات کلیدی')
                ->maxLength(255),
            Forms\Components\TextInput::make('focus_keyword')
                ->label('کلمهٔ کلیدی تمرکز')
                ->maxLength(120)
                ->columnSpanFull()
                ->live(debounce: 750)
                ->helperText('یک عبارت اصلی برای این صفحه (مثلاً «سرویس چینی چکامه»). چراغ‌های زیر از روی همین عبارت ساخته می‌شوند — بدون AI.'),
        ];

        if ($includeOgText) {
            $fields[] = Forms\Components\TextInput::make('og_title')->label('عنوان Open Graph')->maxLength(70);
            $fields[] = Forms\Components\Textarea::make('og_description')->label('توضیح Open Graph')->maxLength(200)->rows(2);
        }

        $fields[] = ShopMediaPicker::image('og_image', 'seo', 'تصویر Open Graph');
        $fields[] = Forms\Components\TextInput::make('canonical_url')->label('آدرس Canonical')->url();
        $fields[] = Forms\Components\Select::make('robots')
            ->label('دستور Robots')
            ->options([
                'index,follow' => 'Index, Follow',
                'index,nofollow' => 'Index, NoFollow',
                'noindex,follow' => 'NoIndex, Follow',
                'noindex,nofollow' => 'NoIndex, NoFollow',
            ])
            ->default('index,follow')
            ->dehydrateStateUsing(fn (?string $state): string => filled($state) ? $state : 'index,follow');

        return $fields;
    }

    protected static function analysisView(string $primaryTitleKey, string $contentKey, ?string $shortKey): ViewField
    {
        return ViewField::make('_seo_content_analysis')
            ->view('filament.components.seo-analysis-panel')
            ->live(debounce: 1200)
            ->viewData(function (Get $get) use ($primaryTitleKey, $contentKey, $shortKey): array {
                $short = $shortKey !== null ? (string) $get($shortKey) : '';

                return [
                    'state' => [
                        'focus_keyword' => (string) $get('focus_keyword'),
                        'primary_title' => (string) $get($primaryTitleKey),
                        'meta_title' => (string) $get('meta_title'),
                        'meta_description' => (string) $get('meta_description'),
                        'content_html' => (string) $get($contentKey),
                        'short_text' => $short,
                    ],
                ];
            })
            ->columnSpanFull()
            ->dehydrated(false);
    }
}
