<?php

namespace App\Filament\Support;

use App\Support\ShopMedia;

class RichContentVideo
{
    /** @var list<string> */
    public const ACCEPTED_MIMES = [
        'video/mp4',
        'video/webm',
        'video/quicktime',
    ];

    public static function appendToHtml(?string $html, string $videoUrl): string
    {
        $videoUrl = trim($videoUrl);
        if ($videoUrl === '') {
            return (string) $html;
        }

        $snippet = self::figureHtml($videoUrl);
        $html = trim((string) $html);

        return $html === '' ? $snippet : $html."\n\n".$snippet;
    }

    public static function figureHtml(string $videoUrl): string
    {
        $src = e($videoUrl);

        return '<figure class="rich-content-video" data-rich-content-video="1">'
            .'<video controls playsinline preload="metadata" src="'.$src.'"></video>'
            .'</figure>';
    }

    public static function urlFromUploadPath(?string $path): ?string
    {
        return ShopMedia::url($path);
    }

    public static function isAllowedExternalUrl(string $url): bool
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        return str_starts_with($url, 'https://') || str_starts_with($url, 'http://');
    }
}
