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

        $formatState = function (mixed $state, string $name): ?string {
            if (blank($state)) {
                return null;
            }

            $format = $name === 'published_at' ? 'Y/m/d' : 'Y/m/d H:i';

            return ShopDate::format($state, $format);
        };

        TextColumn::configureUsing(function (TextColumn $column) use ($formatState): void {
            $name = $column->getName();

            if (! self::isJalaliDateAttribute($name)) {
                return;
            }

            $column->formatStateUsing(fn ($state) => $formatState($state, $name));
        }, isImportant: true);

        TextEntry::configureUsing(function (TextEntry $entry) use ($formatState): void {
            $name = $entry->getName();

            if (! self::isJalaliDateAttribute($name)) {
                return;
            }

            $entry->formatStateUsing(fn ($state) => $formatState($state, $name));
        }, isImportant: true);
    }

    protected static function isJalaliDateAttribute(string $name): bool
    {
        return str_ends_with($name, '_at')
            || in_array($name, ['published_at', 'stock_reserved_until'], true);
    }
}
