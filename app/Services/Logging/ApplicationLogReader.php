<?php

namespace App\Services\Logging;

use App\Support\ShopStoragePaths;
use Carbon\Carbon;

/**
 * Read-only access to Laravel application logs (laravel*.log).
 * Avoids scanning payment logs or loading entire large files into memory.
 */
class ApplicationLogReader
{
    public const RECENT_DAYS = 20;

    private const MAX_SEARCH_LINES = 200;

    private const MAX_SEARCH_FILES = 3;

    private const TAIL_READ_BYTES = 262_144;

    private const MAX_PREVIEW_LINES = 120;

    public function logDirectory(): string
    {
        return ShopStoragePaths::logsDirectory();
    }

    public function dailyLogBasename(string $dateYmd): string
    {
        return 'laravel-'.$dateYmd.'.log';
    }

    public function pathForDailyLog(string $dateYmd): ?string
    {
        $candidate = $this->logDirectory().DIRECTORY_SEPARATOR.$this->dailyLogBasename($dateYmd);

        return $this->readableFile($candidate);
    }

    /**
     * @return list<array{
     *     date: string,
     *     label: string,
     *     basename: ?string,
     *     size_bytes: ?int,
     *     kind: 'log'|'missing',
     *     download_url: ?string
     * }>
     */
    public function recentDayEntries(int $days = self::RECENT_DAYS): array
    {
        $days = max(1, min(60, $days));
        $entries = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $logPath = $this->pathForDailyLog($date);

            if ($logPath === null && $i === 0) {
                $logPath = $this->resolvePathByBasename('laravel.log');
            }

            if ($logPath !== null) {
                $basename = basename($logPath);
                $entries[] = [
                    'date' => $date,
                    'label' => $this->formatDisplayDate($date),
                    'basename' => $basename,
                    'size_bytes' => @filesize($logPath) ?: null,
                    'kind' => 'log',
                    'download_url' => $this->downloadUrlForBasename($basename),
                ];

                continue;
            }

            $entries[] = [
                'date' => $date,
                'label' => $this->formatDisplayDate($date),
                'basename' => null,
                'size_bytes' => null,
                'kind' => 'missing',
                'download_url' => null,
            ];
        }

        return $entries;
    }

    /**
     * @return array{
     *     label: string,
     *     basename: string,
     *     size_bytes: ?int,
     *     kind: 'log',
     *     download_url: ?string
     * }|null
     */
    public function legacySingleLogEntry(): ?array
    {
        $basename = 'laravel.log';
        $path = $this->logDirectory().DIRECTORY_SEPARATOR.$basename;

        if (! is_readable($path)) {
            return null;
        }

        return [
            'label' => 'فایل تجمیعی (laravel.log)',
            'basename' => $basename,
            'size_bytes' => @filesize($path) ?: null,
            'kind' => 'log',
            'download_url' => $this->downloadUrlForBasename($basename),
        ];
    }

    public function downloadUrlForBasename(string $basename): ?string
    {
        if ($this->resolvePathByBasename($basename) === null) {
            return null;
        }

        return route('filament.admin.site-logs.download', ['file' => $basename], absolute: true);
    }

    public function isDownloadableBasename(string $basename): bool
    {
        return (bool) preg_match('/^laravel(-\d{4}-\d{2}-\d{2})?\.log$/', basename($basename));
    }

    public function resolvePathByBasename(string $basename): ?string
    {
        $basename = basename($basename);

        if (! $this->isDownloadableBasename($basename)) {
            return null;
        }

        $candidate = $this->logDirectory().DIRECTORY_SEPARATOR.$basename;

        if (! $this->readableFile($candidate)) {
            return null;
        }

        $normalizedDir = $this->normalizePath($this->logDirectory());
        $normalizedFile = $this->normalizePath($candidate);

        if (! str_starts_with($normalizedFile, $normalizedDir.'/')) {
            return null;
        }

        return $candidate;
    }

    public function mimeTypeForBasename(string $basename): string
    {
        return 'text/plain; charset=UTF-8';
    }

    public function formatFileSize(?int $bytes): string
    {
        if ($bytes === null || $bytes < 0) {
            return '—';
        }

        if ($bytes < 1024) {
            return $bytes.' B';
        }

        if ($bytes < 1024 * 1024) {
            return round($bytes / 1024, 1).' KB';
        }

        return round($bytes / (1024 * 1024), 2).' MB';
    }

    public function formatDisplayDate(?string $dateYmd): string
    {
        if ($dateYmd === null || $dateYmd === '') {
            return '';
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $dateYmd)->shopJalali('Y/m/d');
        } catch (\Throwable) {
            return $dateYmd;
        }
    }

    /**
     * Last error-level lines from today's log (or laravel.log), reading only the file tail.
     *
     * @return array{lines: list<string>, source_basename: ?string, truncated: bool}
     */
    public function tailRecentErrors(): array
    {
        return $this->tailRecentLogLines(self::MAX_PREVIEW_LINES, errorsOnly: true);
    }

    /**
     * @return array{lines: list<string>, source_basename: ?string, truncated: bool}
     */
    public function tailRecentLogLines(int $maxLines = self::MAX_PREVIEW_LINES, bool $errorsOnly = false): array
    {
        $path = $this->pathForDailyLog(now()->format('Y-m-d'))
            ?? $this->resolvePathByBasename('laravel.log');

        if ($path === null) {
            $discovered = $this->discoverLaravelLogFiles();
            $path = $discovered[0]['path'] ?? null;
        }

        if ($path === null) {
            return ['lines' => [], 'source_basename' => null, 'truncated' => false];
        }

        $chunk = $this->readTailBytes($path, self::TAIL_READ_BYTES);
        $lines = $this->splitLines($chunk);

        if ($errorsOnly) {
            $lines = $this->filterErrorLines($lines);
        }

        $truncated = count($lines) > $maxLines;
        if ($truncated) {
            $lines = array_slice($lines, -$maxLines);
        }

        return [
            'lines' => $lines,
            'source_basename' => basename($path),
            'truncated' => $truncated,
        ];
    }

    /**
     * All laravel*.log files on disk (single + daily), newest first.
     *
     * @return list<array{
     *     path: string,
     *     basename: string,
     *     label: string,
     *     date: ?string,
     *     size_bytes: ?int,
     *     download_url: ?string,
     *     modified_at: int
     * }>
     */
    public function discoverLaravelLogFiles(): array
    {
        $pattern = $this->logDirectory().DIRECTORY_SEPARATOR.'laravel*.log';
        $paths = glob($pattern) ?: [];
        $entries = [];

        foreach ($paths as $path) {
            if (! is_readable($path)) {
                continue;
            }

            $basename = basename($path);
            if (! $this->isDownloadableBasename($basename)) {
                continue;
            }

            $date = null;
            if (preg_match('/^laravel-(\d{4}-\d{2}-\d{2})\.log$/', $basename, $matches)) {
                $date = $matches[1];
            }

            $entries[] = [
                'path' => $path,
                'basename' => $basename,
                'label' => $date !== null ? $this->formatDisplayDate($date) : 'فایل تجمیعی (laravel.log)',
                'date' => $date,
                'size_bytes' => @filesize($path) ?: null,
                'download_url' => $this->downloadUrlForBasename($basename),
                'modified_at' => (int) (@filemtime($path) ?: 0),
            ];
        }

        usort($entries, fn (array $a, array $b): int => $b['modified_at'] <=> $a['modified_at']);

        return $entries;
    }

    /**
     * @return array{lines: list<string>, truncated: bool, scanned_files: list<string>}
     */
    public function searchRecent(string $query, int $days = 7): array
    {
        $needle = trim($query);

        if ($needle === '') {
            return ['lines' => [], 'truncated' => false, 'scanned_files' => []];
        }

        $days = max(1, min(14, $days));
        $files = $this->recentLogFilesForScan($days);
        $matches = [];
        $truncated = false;

        foreach ($files as $path) {
            foreach ($this->readMatchingLines($path, $needle) as $line) {
                $matches[] = $line;

                if (count($matches) >= self::MAX_SEARCH_LINES) {
                    $truncated = true;
                    break 2;
                }
            }
        }

        return [
            'lines' => $matches,
            'truncated' => $truncated,
            'scanned_files' => array_map(basename(...), $files),
        ];
    }

    /**
     * @return list<string> absolute paths, newest first, capped
     */
    protected function recentLogFilesForScan(int $days): array
    {
        $files = [];

        for ($i = 0; $i < $days; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $path = $this->pathForDailyLog($date);
            if ($path !== null) {
                $files[] = $path;
            }
        }

        $legacy = $this->resolvePathByBasename('laravel.log');
        if ($legacy !== null && ! in_array($legacy, $files, true)) {
            $files[] = $legacy;
        }

        if (count($files) < self::MAX_SEARCH_FILES) {
            foreach ($this->discoverLaravelLogFiles() as $entry) {
                $path = $entry['path'];
                if (! in_array($path, $files, true)) {
                    $files[] = $path;
                }

                if (count($files) >= self::MAX_SEARCH_FILES) {
                    break;
                }
            }
        }

        return array_slice($files, 0, self::MAX_SEARCH_FILES);
    }

    /**
     * @return array{
     *     directory: string,
     *     directory_exists: bool,
     *     directory_writable: bool,
     *     log_channel: string,
     *     log_stack: string,
     *     discovered_file_count: int,
     *     uses_daily_files: bool,
     *     uses_single_file: bool
     * }
     */
    public function storageDiagnostics(): array
    {
        $dir = $this->logDirectory();
        $exists = is_dir($dir);
        $stack = (string) env('LOG_STACK', 'single');

        return [
            'directory' => $dir,
            'directory_exists' => $exists,
            'directory_writable' => $exists && is_writable($dir),
            'log_channel' => (string) config('logging.default'),
            'log_stack' => $stack,
            'discovered_file_count' => count($this->discoverLaravelLogFiles()),
            'uses_daily_files' => str_contains($stack, 'daily'),
            'uses_single_file' => str_contains($stack, 'single'),
        ];
    }

    /**
     * @return \Generator<int, string>
     */
    protected function readMatchingLines(string $path, string $needle): \Generator
    {
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                if (stripos($line, $needle) !== false) {
                    yield rtrim($line, "\r\n");
                }
            }
        } finally {
            fclose($handle);
        }
    }

    protected function readTailBytes(string $path, int $maxBytes): string
    {
        $size = @filesize($path);

        if ($size === false || $size <= 0) {
            return '';
        }

        $readFrom = max(0, $size - $maxBytes);
        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        try {
            if ($readFrom > 0) {
                fseek($handle, $readFrom);
            }

            $data = stream_get_contents($handle);

            return is_string($data) ? $data : '';
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return list<string>
     */
    protected function splitLines(string $chunk): array
    {
        if ($chunk === '') {
            return [];
        }

        return preg_split("/\r\n|\n|\r/", $chunk) ?: [];
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    protected function filterErrorLines(array $lines): array
    {
        $out = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if (preg_match('/\.(ERROR|CRITICAL|EMERGENCY|ALERT):/i', $line)) {
                $out[] = $line;
            }
        }

        return $out;
    }

    protected function readableFile(string $path): ?string
    {
        return is_file($path) && is_readable($path) ? $path : null;
    }

    protected function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
