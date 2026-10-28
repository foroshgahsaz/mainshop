<?php

namespace App\Services\Shop;

use App\Models\ProductFamily;
use App\Services\Settings\HomepageSettingsService;
use Illuminate\Support\Collection;

class HomepageHeroHubService
{
    public function __construct(
        protected HomepageSettingsService $homepageSettings,
        protected ProductFamilyHomepagePopupService $popupService,
    ) {}

    /**
     * @return array{
     *     sidebar_title: string,
     *     sidebar_links: list<array{label: string, url: string}>,
     *     quick_families: list<array{id: int, name: string, image: ?string, url: string}>
     * }
     */
    public function payload(): array
    {
        $sidebarFamily = $this->resolveSidebarFamily();
        $sidebarLinks = [];
        $sidebarTitle = 'سرویس چینی';

        if ($sidebarFamily !== null) {
            $sidebarTitle = (string) $sidebarFamily->name;
            $popup = $this->popupService->payloadForFamily($sidebarFamily);
            if ($popup !== null) {
                $sidebarLinks = $popup['items'];
            }
        }

        return [
            'sidebar_title' => $sidebarTitle,
            'sidebar_links' => $sidebarLinks,
            'quick_families' => $this->resolveQuickFamilies(),
        ];
    }

    protected function resolveSidebarFamily(): ?ProductFamily
    {
        $id = $this->homepageSettings->heroSidebarFamilyId();
        if ($id !== null) {
            return ProductFamily::query()
                ->where('is_active', true)
                ->whereKey($id)
                ->first();
        }

        return ProductFamily::query()
            ->where('is_active', true)
            ->where('slug', 'chinese-service')
            ->first();
    }

    /**
     * @return list<array{id: int, name: string, image: ?string, url: string}>
     */
    protected function resolveQuickFamilies(): array
    {
        $ids = $this->homepageSettings->heroQuickFamilyIds();
        if ($ids === []) {
            $ids = ProductFamily::query()
                ->where('is_active', true)
                ->whereIn('slug', ['pars-opal', 'kitchenware', 'crystal-service'])
                ->orderBy('position')
                ->pluck('id')
                ->take(3)
                ->all();
        }

        if ($ids === []) {
            return [];
        }

        $families = ProductFamily::query()
            ->where('is_active', true)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $out = [];
        foreach ($ids as $id) {
            $family = $families->get($id);
            if ($family === null) {
                continue;
            }

            $out[] = [
                'id' => (int) $family->id,
                'name' => (string) $family->name,
                'image' => $family->image,
                'url' => route('products.index', ['product_family_id' => $family->id]),
            ];
        }

        return $out;
    }
}
