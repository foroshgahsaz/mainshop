<?php

namespace App\Services\Shop;

use App\Models\CreditPlan;
use App\Services\Cache\ShopCacheService;
use Illuminate\Support\Facades\Schema;

/**
 * اطمینان از وجود کارت طرح داریک در credit_plans (صفحه /plans و ویجت خانه).
 */
final class EnsureDarikCreditPlan
{
    public const PLAN_URL = '/plans/darik/';

    public const PLAN_NAME = 'داریک';

    public function __construct(
        protected ShopCacheService $cache,
    ) {}

    /** @return bool true اگر ردیف جدید ساخته شد */
    public function ensure(): bool
    {
        if (! Schema::hasTable('credit_plans')) {
            return false;
        }

        $exists = CreditPlan::query()
            ->where(function ($q): void {
                $q->where('name', self::PLAN_NAME)
                    ->orWhere('plan_url', self::PLAN_URL)
                    ->orWhere('plan_url', 'like', '%/plans/darik%');
            })
            ->exists();

        if ($exists) {
            return false;
        }

        $maxPosition = (int) CreditPlan::query()->max('position');

        $attrs = [
            'name' => self::PLAN_NAME,
            'icon' => null,
            'short_description' => 'کیف پول اعتباری',
            'plan_url' => self::PLAN_URL,
            'position' => $maxPosition + 1,
            'show_on_homepage_desktop' => true,
            'is_active' => true,
        ];

        if (Schema::hasColumn('credit_plans', 'credit_gateway')) {
            $attrs['credit_gateway'] = 'darik';
        }

        if (Schema::hasColumn('credit_plans', 'cash_gateway')) {
            $attrs['cash_gateway'] = null;
        }

        CreditPlan::query()->create($attrs);
        $this->cache->forgetHome();

        return true;
    }
}
