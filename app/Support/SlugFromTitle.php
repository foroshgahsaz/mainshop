<?php

namespace App\Support;

final class SlugFromTitle
{
    /** Persian/Latin title → URL slug: spaces become hyphens (e.g. «سرویس چینی» → «سرویس-چینی»). */
    public static function fromProductName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $slug = preg_replace('/[\h\v\x{00A0}]+/u', '-', $name) ?? $name;
        $slug = preg_replace('/-+/u', '-', $slug) ?? $slug;

        return trim($slug, '-');
    }
}
