<?php

namespace App\Filament\Representative\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $title = 'داشبورد نمایندگی';

    protected static ?string $navigationLabel = 'داشبورد';
}
