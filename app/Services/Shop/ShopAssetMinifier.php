<?php

namespace App\Services\Shop;

use App\Services\Settings\ShopAssetSettingsService;
use App\Support\ShopAsset;
use Illuminate\Support\Facades\Storage;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use RuntimeException;

final class ShopAssetMinifier
{
    /**
     * @param  list<string>|null  $onlyKeys کلیدهای ASSET_DEFINITIONS؛ null = همه
     * @return list<array{key: string, source: string, output: string, bytes_before: int, bytes_after: int}>
     */
    public function build(?array $onlyKeys = null): array
    {
        $disk = Storage::disk('public');
        $results = [];

        foreach (ShopAssetSettingsService::ASSET_DEFINITIONS as $key => $definition) {
            if ($onlyKeys !== null && ! in_array($key, $onlyKeys, true)) {
                continue;
            }

            $relative = $definition['path'];
            $source = public_path($relative);

            if (! is_file($source)) {
                continue;
            }

            $outputRelative = ShopAsset::minStorageRelativePath($relative);
            $targetDir = dirname($disk->path($outputRelative));

            if (! is_dir($targetDir)) {
                if (! @mkdir($targetDir, 0775, true) && ! is_dir($targetDir)) {
                    throw new RuntimeException(
                        'پوشهٔ خروجی فشرده‌سازی قابل ساخت نیست ('.$targetDir.'). روی سرور: php artisan shop:fix-storage-permissions'
                    );
                }
            }

            $input = (string) file_get_contents($source);
            $bytesBefore = strlen($input);

            $output = $definition['type'] === 'css'
                ? (new CSS($source))->minify()
                : (new JS($source))->minify();

            if ($definition['type'] === 'css') {
                $output = $this->rewriteCssUrlsForStorageDelivery($output, $relative);
            }

            if (! $disk->put($outputRelative, $output)) {
                throw new RuntimeException(
                    'نوشتن فایل فشرده ممکن نیست ('.$outputRelative.'). پوشه storage/app/public/shop-min باید برای www-data قابل نوشتن باشد؛ php artisan shop:fix-storage-permissions'
                );
            }

            $results[] = [
                'key' => $key,
                'source' => $relative,
                'output' => $outputRelative,
                'bytes_before' => $bytesBefore,
                'bytes_after' => strlen($output),
            ];
        }

        return $results;
    }

    /**
     * فایل min از /data/shop-min/css سرو می‌شود؛ url(../…) باید به مسیر webpublic (مثلاً /shop/fonts) تبدیل شود.
     */
    private function rewriteCssUrlsForStorageDelivery(string $css, string $sourcePublicRelative): string
    {
        $baseDir = str_replace('\\', '/', dirname($sourcePublicRelative));

        return (string) preg_replace_callback(
            '/url\((["\']?)(\.\.?\/[^)\'"]+)\1\)/',
            function (array $matches) use ($baseDir): string {
                $quote = $matches[1];
                $resolved = $this->resolvePublicRelativePath($baseDir, $matches[2]);

                return 'url('.$quote.asset($resolved).$quote.')';
            },
            $css
        );
    }

    private function resolvePublicRelativePath(string $baseDir, string $relativeUrl): string
    {
        $relativeUrl = str_replace('\\', '/', $relativeUrl);
        $segments = explode('/', $baseDir.'/'.$relativeUrl);
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                array_pop($resolved);

                continue;
            }

            $resolved[] = $segment;
        }

        return implode('/', $resolved);
    }
}
