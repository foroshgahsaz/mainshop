<?php

namespace App\Support;

final class OrderedProductIds
{
    public const MAX_CURATED = 30;

    /** @return list<int> */
    public static function parse(mixed $raw): array
    {
        if (is_array($raw)) {
            return self::normalize($raw);
        }

        $text = trim((string) $raw);
        if ($text === '') {
            return [];
        }

        $text = str_replace(['،', ';', "\n", "\r", "\t"], ',', $text);

        return self::normalize(explode(',', $text));
    }

    /**
     * @param  iterable<mixed>  $parts
     * @return list<int>
     */
    protected static function normalize(iterable $parts): array
    {
        return collect($parts)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->take(self::MAX_CURATED)
            ->values()
            ->all();
    }
}