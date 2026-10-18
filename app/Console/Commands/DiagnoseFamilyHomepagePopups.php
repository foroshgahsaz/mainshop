<?php

namespace App\Console\Commands;

use App\Models\ProductFamily;
use App\Services\Cache\ShopCacheService;
use App\Services\Settings\HomepageSettingsService;
use App\Services\Shop\ProductFamilyHomepagePopupService;
use Illuminate\Console\Command;

class DiagnoseFamilyHomepagePopups extends Command
{
    protected $signature = 'shop:diagnose-family-homepage-popups';

    protected $description = 'Diagnose homepage product-family popup settings and cache payload';

    public function handle(
        HomepageSettingsService $homepage,
        ShopCacheService $cache,
        ProductFamilyHomepagePopupService $popup,
    ): int {
        $this->components->info('Product family homepage popups');
        $this->newLine();

        $mode = $homepage->taxonomyMode();
        $this->line('taxonomy_mode: '.$mode);
        if ($mode !== HomepageSettingsService::TAXONOMY_PRODUCT_FAMILIES) {
            $this->components->warn('Admin → صفحه اصلی فروشگاه → «خانواده‌های محصول»');
        }

        $this->newLine();
        foreach (ProductFamily::query()->where('is_active', true)->orderBy('position')->get() as $family) {
            $enabled = (bool) $family->homepage_popup_enabled;
            $itemCount = $family->homepagePopupItems()->where('is_active', true)->count();
            $payload = $popup->payloadForFamily($family);
            $status = $payload ? 'popup ON ('.count($payload['items']).' links)' : ($enabled ? 'enabled but no active links' : 'direct link');
            $this->line(sprintf('  [%d] %s — %s', $family->id, $family->name, $status));
            if ($enabled && $itemCount === 0) {
                $this->components->warn('    Add links in Admin → خانواده محصول → پاپ‌آپ صفحه اصلی');
            }
        }

        $cached = $cache->homePayload()['family_homepage_popups'] ?? [];
        $this->newLine();
        $this->line('homePayload family_homepage_popups families: '.count($cached));
        if ($cached === []) {
            $this->components->warn('Cache has no popups — enable toggle + at least one link, then shop:deploy-recover');
        }

        return self::SUCCESS;
    }
}
