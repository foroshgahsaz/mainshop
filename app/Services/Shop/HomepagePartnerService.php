<?php

namespace App\Services\Shop;

use App\Models\Partner;
use App\Support\ShopFormatter;

class HomepagePartnerService
{
    /**
     * @return list<array{name: string, logo: ?string, link: ?string}>
     */
    public function homepageItems(): array
    {
        return Partner::query()
            ->active()
            ->homepage()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function (Partner $partner) {
                $logo = $partner->logo
                    ? ShopFormatter::sectionImage('partners', $partner->logo, '')
                    : null;
                if ($logo === '') {
                    $logo = null;
                }

                $link = trim((string) ($partner->link ?? ''));
                if ($link !== '' && ! str_starts_with($link, 'http://') && ! str_starts_with($link, 'https://')) {
                    $link = url($link);
                }
                if ($link === '') {
                    $link = null;
                }

                return [
                    'name' => $partner->name,
                    'logo' => $logo,
                    'link' => $link,
                ];
            })
            ->values()
            ->all();
    }
}
