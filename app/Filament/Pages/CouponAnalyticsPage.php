<?php

namespace App\Filament\Pages;

use App\Models\Coupon;
use App\Services\Checkout\CouponAnalyticsService;
use App\Support\AdminAccess;
use Filament\Pages\Page;

class CouponAnalyticsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'coupons/{record}/analytics';

    protected static string $view = 'filament.pages.coupon-analytics';

    protected static ?string $title = 'آنالیز کد تخفیف';

    public Coupon $coupon;

    /** @var array<string, mixed> */
    public array $summary = [];

    public static function canAccess(): bool
    {
        return AdminAccess::canManageShopInAdmin();
    }

    public function mount(int|string $record, CouponAnalyticsService $analytics): void
    {
        $this->coupon = Coupon::query()->findOrFail($record);
        $this->summary = $analytics->summary($this->coupon);
    }

    public function getTitle(): string
    {
        return 'آنالیز: '.$this->coupon->code;
    }
}
