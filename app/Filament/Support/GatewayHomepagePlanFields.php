<?php

namespace App\Filament\Support;

use Filament\Forms;

class GatewayHomepagePlanFields
{
    public const DEFAULT_INTRO = 'خرید اعتباری و پرداخت آسان';

    /** @param  array<string, mixed>  $gatewaySettings */
    public static function mountState(array $gatewaySettings): array
    {
        return [
            'show_on_homepage' => (bool) ($gatewaySettings['show_on_homepage'] ?? false),
            'homepage_intro' => (string) ($gatewaySettings['homepage_intro'] ?? self::DEFAULT_INTRO),
            'homepage_plan_url' => (string) ($gatewaySettings['homepage_plan_url'] ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    public static function saveState(array $formData): array
    {
        return [
            'show_on_homepage' => $formData['show_on_homepage'] ?? false,
            'homepage_intro' => trim((string) ($formData['homepage_intro'] ?? self::DEFAULT_INTRO)),
            'homepage_plan_url' => trim((string) ($formData['homepage_plan_url'] ?? '')),
        ];
    }

    public static function section(): Forms\Components\Section
    {
        return Forms\Components\Section::make('نمایش در صفحه اصلی')
            ->description('کارت طرح اعتباری بالای «جدیدترین محصولات» — لوگو از آیکون درگاه و عنوان از نام درگاه.')
            ->schema([
                Forms\Components\Toggle::make('show_on_homepage')
                    ->label('نمایش کارت این درگاه در صفحه اصلی')
                    ->live(),
                Forms\Components\TextInput::make('homepage_plan_url')
                    ->label('آدرس صفحه طرح')
                    ->placeholder('/plans/digipay-leasing/')
                    ->helperText('مسیر نسبی یا URL کامل صفحه معرفی طرح.')
                    ->maxLength(500)
                    ->visible(fn (Forms\Get $get): bool => (bool) $get('show_on_homepage'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('homepage_intro')
                    ->label('معرفی کوتاه')
                    ->placeholder(self::DEFAULT_INTRO)
                    ->maxLength(120)
                    ->visible(fn (Forms\Get $get): bool => (bool) $get('show_on_homepage'))
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->collapsible();
    }
}
