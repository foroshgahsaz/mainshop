<?php

namespace App\Support;

final class SlugFromTitle
{
    /** Persian/Latin title → URL slug: only whitespace → hyphen; all other chars kept. */
    public static function fromProductName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        // Normal space, NBSP, tab, newline — not \h (avoids stripping during IME / odd Unicode)
        $slug = preg_replace('/[\x{0020}\x{00A0}\x{0009}\x{000A}\x{000D}]+/u', '-', $name) ?? $name;
        $slug = preg_replace('/-+/u', '-', $slug) ?? $slug;

        return trim($slug, '-');
    }
}
