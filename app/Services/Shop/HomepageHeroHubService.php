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
            $sidebarLinks = $this->popupService->activeLinkItemsForFamily($sidebarFamily);
        }

        return [
            'sidebar_title' => $sidebarTitle,
            'sidebar_links' => $sidebarLinks,
            'quick_families' => $this->resolveQuickFamilies(),
        ];
    }

    protected function resolveSidebarFamily(): ?ProductFamily
    {
        $configuredId = $this->homepageSettings->heroSidebarFamilyId();

        $candidateIds = collect();
        if ($configuredId !== null) {
            $candidateIds->push($configuredId);
        }

        ProductFamily::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('slug', 'chinese-service')
                    ->orWhere('name', 'سرویس چینی');
            })
            ->pluck('id')
            ->each(fn (int $id) => $candidateIds->push($id));

        $candidateIds = $candidateIds->unique()->filter()->values();

        $bestFamily = null;
        $bestLinkCount = 0;

        foreach ($candidateIds as $familyId) {
            $family = ProductFamily::query()
                ->where('is_active', true)
                ->whereKey($familyId)
                ->first();

            if ($family === null) {
                continue;
            }

            $linkCount = count($this->popupService->activeLinkItemsForFamily($family));
            if ($linkCount > $bestLinkCount) {
                $bestLinkCount = $linkCount;
                $bestFamily = $family;
            }
        }

        if ($bestFamily !== null && $bestLinkCount > 0) {
            return $bestFamily;
        }

        if ($configuredId !== null) {
            return ProductFamily::query()
                ->where('is_active', true)
                ->whereKey($configuredId)
                ->first();
        }

        return ProductFamily::query()
            ->where('is_active', true)
            ->where('slug', 'chinese-service')
            ->first()
            ?? ProductFamily::query()
                ->where('is_active', true)
                ->where('name', 'سرویس چینی')
                ->orderBy('position')
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
