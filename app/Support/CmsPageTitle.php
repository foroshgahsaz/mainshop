<?php

namespace App\Support;

use App\Models\Page;

final class CmsPageTitle
{
    /** عنوان تب مرورگر و OG — بدون پسوند برند قدیمی (چاپینو و …). */
    public static function forPage(Page $page): string
    {
        $title = trim((string) $page->title);

        if ($title !== '') {
            return $title;
        }

        return self::cleanMetaTitle((string) ($page->meta_title ?? ''));
    }

    public static function cleanMetaTitle(string $metaTitle): string
    {
        $metaTitle = trim($metaTitle);

        if ($metaTitle === '') {
            return site_name();
        }

        if (str_contains($metaTitle, '|')) {
            $metaTitle = trim(explode('|', $metaTitle, 2)[0]);
        }

        foreach (['چاپینو', 'Chapino', 'CHAPINO'] as $legacy) {
            $metaTitle = str_replace($legacy, '', $metaTitle);
        }

        $metaTitle = trim(preg_replace('/\s{2,}/u', ' ', $metaTitle) ?? '');

        return $metaTitle !== '' ? $metaTitle : site_name();
    }
}
