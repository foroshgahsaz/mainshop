<?php

namespace Tests\Unit;

use App\Support\LogDirectoryResolver;
use App\Support\ShopStoragePaths;
use Tests\TestCase;

class LogDirectoryResolverTest extends TestCase
{
    public function test_finds_file_in_fallback_storage_logs_directory(): void
    {
        config(['shop.storage.logs_directory' => '/data/logs']);

        $fallback = storage_path('logs');
        if (! is_dir($fallback)) {
            mkdir($fallback, 0775, true);
        }

        $marker = 'payments-resolver-test-'.uniqid().'.log';
        $path = $fallback.DIRECTORY_SEPARATOR.$marker;
        file_put_contents($path, 'test');

        try {
            $found = LogDirectoryResolver::findReadableFile($marker);

            $this->assertSame($path, $found);
        } finally {
            @unlink($path);
        }
    }

    public function test_search_directories_lists_primary_first(): void
    {
        config(['shop.storage.logs_directory' => '/data/logs']);

        $dirs = LogDirectoryResolver::searchDirectories();

        $this->assertSame('/data/logs', $dirs[0]);
        $this->assertContains(storage_path('logs'), $dirs);
    }
}
