<?php

namespace App\Filament\Representative\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class CreateDraftOrder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'سفارش جدید';

    protected static ?string $title = 'ثبت پیش‌سفارش';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.representative.pages.create-draft-order';

    protected static string $layout = 'filament-panels::components.layout.representative';

    public function getMaxContentWidth(): MaxWidth|string|null
    {
        return MaxWidth::Full;
    }
}
