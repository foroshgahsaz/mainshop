<?php

namespace App\Support;

final class PublicDiskFileMime
{
    public static function forPath(string $path): ?string
    {
        $path = strtolower($path);

        if (str_ends_with($path, '.css')) {
            return 'text/css; charset=UTF-8';
        }

        if (str_ends_with($path, '.js')) {
            return 'application/javascript; charset=UTF-8';
        }

        if (str_ends_with($path, '.json')) {
            return 'application/json; charset=UTF-8';
        }

        if (str_ends_with($path, '.svg')) {
            return 'image/svg+xml';
        }

        if (preg_match('/\.(jpe?g|png|gif|webp|avif)$/i', $path) === 1) {
            return match (true) {
                str_ends_with($path, '.png') => 'image/png',
                str_ends_with($path, '.gif') => 'image/gif',
                str_ends_with($path, '.webp') => 'image/webp',
                str_ends_with($path, '.avif') => 'image/avif',
                default => 'image/jpeg',
            };
        }

        if (str_ends_with($path, '.woff2')) {
            return 'font/woff2';
        }

        if (str_ends_with($path, '.woff')) {
            return 'font/woff';
        }

        return null;
    }
}
