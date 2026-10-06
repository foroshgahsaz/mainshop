<?php

namespace App\Providers;

use App\Support\ShopDate;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

class JalaliDateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Carbon::setLocale('fa');
        Date::use(Carbon::class);

        Carbon::macro('shopJalali', function (?string $format = null): string {
            /** @var CarbonInterface $this */
            return ShopDate::format($this, $format ?? 'Y/m/d H:i');
        });

        TextColumn::macro('shopJalaliDateTime', function (?string $format = 'Y/m/d H:i') {
            /** @var TextColumn $this */
            return $this->formatStateUsing(
                fn ($state) => blank($state) ? null : ShopDate::format($state, $format)
            );
        });

        TextColumn::macro('shopJalaliDate', function () {
            /** @var TextColumn $this */
            return $this->shopJalaliDateTime('Y/m/d');
        });

        TextEntry::macro('shopJalaliDateTime', function (?string $format = 'Y/m/d H:i') {
            /** @var TextEntry $this */
            return $this->formatStateUsing(
                fn ($state) => blank($state) ? null : ShopDate::format($state, $format)
            );
        });

        TextEntry::macro('shopJalaliDate', function () {
            /** @var TextEntry $this */
            return $this->shopJalaliDateTime('Y/m/d');
        });
    }
}
