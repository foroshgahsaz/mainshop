<?php

namespace App\Services\Shop;

use App\Services\Settings\ShopAssetSettingsService;
use App\Support\ShopAsset;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;

final class ShopAssetMinifier
{
    /**
     * @param  list<string>|null  $onlyKeys کلیدهای ASSET_DEFINITIONS؛ null = همه
     * @return list<array{key: string, source: string, output: string, bytes_before: int, bytes_after: int}>
     */
    public function build(?array $onlyKeys = null): array
    {
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

            $minRelative = ShopAsset::minPathFor($relative);
            $target = public_path($minRelative);

            $input = (string) file_get_contents($source);
            $bytesBefore = strlen($input);

            $output = $definition['type'] === 'css'
                ? (new CSS($source))->minify()
                : (new JS($source))->minify();

            if (! is_dir(dirname($target))) {
                mkdir(dirname($target), 0755, true);
            }

            file_put_contents($target, $output);

            $results[] = [
                'key' => $key,
                'source' => $relative,
                'output' => $minRelative,
                'bytes_before' => $bytesBefore,
                'bytes_after' => strlen($output),
            ];
        }

        return $results;
    }
}
