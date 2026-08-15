<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Support\ProductPlaceholder;
use Illuminate\Console\Command;

class EnsureProductImages extends Command
{
    protected $signature = 'shop:ensure-product-images';

    protected $description = 'Create placeholder files for product images missing on disk';

    public function handle(): int
    {
        $count = 0;

        ProductImage::query()
            ->select(['id', 'image'])
            ->orderBy('id')
            ->each(function (ProductImage $image) use (&$count): void {
                if (blank($image->image)) {
                    return;
                }

                $path = storage_path('app/public/'.$image->image);
                $needsReplace = ! is_file($path) || filesize($path) < 512;

                if (! $needsReplace) {
                    return;
                }

                ProductPlaceholder::ensure($image->image, 'محصول');
                $count++;
            });

        $this->info("Created {$count} missing product image file(s).");

        return self::SUCCESS;
    }
}
