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
     *     sidebar_links: list<array{label: string, url: string, image: ?string}>,
     *     quick_families: list<array{id: int, name: string, image: ?string, url: string}>
     * }
     */
    public function payload(): array
    {
        $sidebarTitle = $this->homepageSettings->heroSidebarTitle();
        $sidebarLinks = $this->buildSidebarLinksForDisplay();

        return [
            'sidebar_title' => $sidebarTitle !== '' ? $sidebarTitle : 'سرویس چینی',
            'sidebar_links' => $sidebarLinks,
            'quick_families' => $this->resolveQuickFamilies(),
        ];
    }

    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function buildSidebarLinksForDisplay(): array
    {
        $links = [];
        $includedFamilyIds = [];

        $primary = $this->resolvePrimaryFamilyWithSubLinks();
        if ($primary !== null) {
            foreach ($this->popupService->activeLinkItemsForFamily($primary) as $link) {
                $links[] = $link;
            }
            $includedFamilyIds[(int) $primary->id] = true;
        }

        if ($this->sidebarFamiliesColumnExists()) {
            $families = ProductFamily::query()
                ->where('is_active', true)
                ->where('show_on_homepage_sidebar', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            foreach ($families as $family) {
                if (isset($includedFamilyIds[(int) $family->id])) {
                    continue;
                }

                foreach ($this->popupService->sidebarLinksForFamily($family) as $link) {
                    $links[] = $link;
                }
            }
        }

        if ($links !== []) {
            return $links;
        }

        $fallbackFamily = $this->resolveSidebarFamily();
        if ($fallbackFamily === null) {
            return [];
        }

        return $this->popupService->sidebarLinksForFamily($fallbackFamily);
    }

    /**
     * @return list<array{label: string, url: string, image: ?string}>
     */
    public function buildSidebarLinksForStorage(): array
    {
        $links = [];
        $includedFamilyIds = [];

        $primary = $this->resolvePrimaryFamilyWithSubLinks();
        if ($primary !== null) {
            foreach ($this->popupService->activeLinkItemsForFamily($primary, resolveImageUrls: false) as $link) {
                $links[] = $link;
            }
            $includedFamilyIds[(int) $primary->id] = true;
        }

        if ($this->sidebarFamiliesColumnExists()) {
            $families = ProductFamily::query()
                ->where('is_active', true)
                ->where('show_on_homepage_sidebar', true)
                ->orderBy('position')
                ->orderBy('id')
                ->get();

            foreach ($families as $family) {
                if (isset($includedFamilyIds[(int) $family->id])) {
                    continue;
                }

                $stored = $this->popupService->storedLinkItemsForFamily($family);
                if ($stored !== []) {
                    foreach ($stored as $link) {
                        $links[] = $link;
                    }

                    continue;
                }

                $links[] = [
                    'label' => (string) $family->name,
                    'url' => route('products.index', ['product_family_id' => $family->id]),
                    'image' => trim((string) ($family->image ?? '')) ?: null,
                ];
            }
        }

        return $links;
    }

    protected function resolvePrimaryFamilyWithSubLinks(): ?ProductFamily
    {
        $candidates = collect();

        $configuredId = $this->homepageSettings->heroSidebarFamilyId();
        if ($configuredId !== null) {
            $candidates->push($configuredId);
        }

        ProductFamily::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where('slug', 'chinese-service')
                    ->orWhere('name', 'سرویس چینی');
            })
            ->pluck('id')
            ->each(fn (int $id) => $candidates->push($id));

        $best = null;
        $bestCount = 0;

        foreach ($candidates->unique()->filter() as $familyId) {
            $family = ProductFamily::query()
                ->where('is_active', true)
                ->whereKey($familyId)
                ->first();

            if ($family === null) {
                continue;
            }

            $count = count($this->popupService->storedLinkItemsForFamily($family));
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $family;
            }
        }

        return $bestCount > 0 ? $best : null;
    }

    protected function sidebarFamiliesColumnExists(): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn('product_families', 'show_on_homepage_sidebar');
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
            $ids = $this->defaultQuickFamilyIds();
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

    /** @return list<int> */
    protected function defaultQuickFamilyIds(): array
    {
        $slugs = ['pars-opal', 'kitchenware', 'crystal-service', 'crystal', 'opal-pars'];
        $names = ['پارس اپال', 'پارس اوپال', 'لوازم آشپزخانه', 'سرویس بلور', 'سرویس بلور و کریستال'];

        $bySlug = ProductFamily::query()
            ->where('is_active', true)
            ->whereIn('slug', $slugs)
            ->orderBy('position')
            ->pluck('id')
            ->all();

        if (count($bySlug) >= 3) {
            return array_slice($bySlug, 0, 3);
        }

        $byName = ProductFamily::query()
            ->where('is_active', true)
            ->whereIn('name', $names)
            ->orderBy('position')
            ->pluck('id')
            ->all();

        return collect([...$bySlug, ...$byName])
            ->unique()
            ->take(3)
            ->values()
            ->all();
    }
}
