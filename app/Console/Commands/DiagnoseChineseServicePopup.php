<?php

namespace App\Console\Commands;

use App\Models\ProductFamily;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Shop\ChineseServiceBrandPopupService;
use Illuminate\Console\Command;

class DiagnoseChineseServicePopup extends Command
{
    protected $signature = 'shop:diagnose-chinese-service-popup';

    protected $description = 'Diagnose homepage «سرویس چینی» brand popup (taxonomy, family match, cache payload)';

    public function handle(
        HomepageSettingsService $homepage,
        ShopCacheService $cache,
        ChineseServiceBrandPopupService $popup,
    ): int {
        $this->components->info('Homepage Chinese service popup diagnostics');
        $this->newLine();

        $taxonomyMode = $homepage->taxonomyMode();
        $this->line('taxonomy_mode (settings): '.$taxonomyMode);
        if ($taxonomyMode !== HomepageSettingsService::TAXONOMY_PRODUCT_FAMILIES) {
            $this->components->warn('Expected product_families — set Admin → صفحه اصلی فروشگاه → خانواده‌های محصول');
        }

        $this->newLine();
        $this->line('Active product families:');
        $families = ProductFamily::query()->where('is_active', true)->orderBy('position')->get();
        if ($families->isEmpty()) {
            $this->components->error('No active product families in database.');
        } else {
            foreach ($families as $family) {
                $match = $popup->matchesFamily($family) ? ' ← popup target' : '';
                $this->line(sprintf('  [%d] %s (slug: %s)%s', $family->id, $family->name, $family->slug, $match));
            }
        }

        $matched = $families->first(fn (ProductFamily $f) => $popup->matchesFamily($f));
        $this->newLine();
        if ($matched === null) {
            $this->components->error('No family matches config (name «سرویس چینی» or slug chinese-service).');
        } else {
            $payload = $popup->payloadForFamily($matched);
            $this->line('payloadForFamily: '.($payload ? 'OK ('.count($payload['brands']).' brands)' : 'null'));
        }

        $hp = $cache->homePayload();
        $this->newLine();
        $this->line('homePayload taxonomy_mode: '.($hp['taxonomy_mode'] ?? '(missing)'));
        $cachedPopup = $hp['chinese_service_brand_popup'] ?? null;
        if ($cachedPopup === null) {
            $this->components->warn('homePayload chinese_service_brand_popup is NULL (popup will not show).');
            $this->line('Run: php artisan cache:clear  OR  php artisan shop:deploy-recover');
        } else {
            $this->components->info('homePayload chinese_service_brand_popup: family_id='.$cachedPopup['family_id'].', brands='.count($cachedPopup['brands']));
        }

        $appVersion = trim((string) @shell_exec('git rev-parse --short HEAD 2>/dev/null'));
        if ($appVersion !== '') {
            $this->newLine();
            $this->line('git HEAD: '.$appVersion);
        }

        return self::SUCCESS;
    }
}
