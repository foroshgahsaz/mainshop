<?php

namespace Tests\Unit;

use App\Services\Logging\ApplicationLogReader;
use Tests\TestCase;

class ApplicationLogReaderTest extends TestCase
{
    public function test_only_laravel_log_basenames_are_downloadable(): void
    {
        $reader = app(ApplicationLogReader::class);

        $this->assertTrue($reader->isDownloadableBasename('laravel.log'));
        $this->assertTrue($reader->isDownloadableBasename('laravel-2026-10-05.log'));
        $this->assertFalse($reader->isDownloadableBasename('payments-2026-10-05.log'));
        $this->assertFalse($reader->isDownloadableBasename('../.env'));
    }

    public function test_tail_filters_error_level_lines(): void
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $basename = 'laravel-'.now()->format('Y-m-d').'.log';
        $path = $dir.DIRECTORY_SEPARATOR.$basename;

        file_put_contents($path, implode("\n", [
            '[2026-10-05 10:00:00] production.INFO: ok',
            '[2026-10-05 10:00:01] production.ERROR: something broke',
        ])."\n");

        $reader = app(ApplicationLogReader::class);
        $preview = $reader->tailRecentErrors();

        $this->assertSame($basename, $preview['source_basename']);
        $this->assertCount(1, $preview['lines']);
        $this->assertStringContainsString('ERROR', $preview['lines'][0]);

        @unlink($path);
    }

    public function test_discover_includes_single_and_daily_logs(): void
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $single = $dir.DIRECTORY_SEPARATOR.'laravel.log';
        $daily = $dir.DIRECTORY_SEPARATOR.'laravel-2026-01-01.log';
        file_put_contents($single, "line\n");
        file_put_contents($daily, "line\n");

        $reader = app(ApplicationLogReader::class);
        $found = $reader->discoverLaravelLogFiles();
        $basenames = array_column($found, 'basename');

        $this->assertContains('laravel.log', $basenames);
        $this->assertContains('laravel-2026-01-01.log', $basenames);

        @unlink($single);
        @unlink($daily);
    }

    public function test_today_calendar_entry_falls_back_to_laravel_log(): void
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $single = $dir.DIRECTORY_SEPARATOR.'laravel.log';
        file_put_contents($single, "ok\n");

        $reader = app(ApplicationLogReader::class);
        $today = $reader->recentDayEntries(1)[0];

        $this->assertSame('log', $today['kind']);
        $this->assertSame('laravel.log', $today['basename']);
        $this->assertNotNull($today['download_url']);

        @unlink($single);
    }
}
