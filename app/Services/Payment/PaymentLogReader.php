<?php

namespace App\Services\Payment;

use App\Support\LogDirectoryResolver;
use App\Support\ShopStoragePaths;
use Carbon\Carbon;

class PaymentLogReader
{
    public const RECENT_DAYS = 20;

    private const MAX_MATCH_LINES = 800;

    public function logDirectory(): string
    {
        return ShopStoragePaths::logsDirectory();
    }

    public function dailyLogBasename(string $dateYmd): string
    {
        return 'payments-'.$dateYmd.'.log';
    }

    public function archiveBasename(string $dateYmd): string
    {
        return 'payments-'.$dateYmd.'.log.zip';
    }

    public function pathForDailyLog(string $dateYmd): ?string
    {
        return LogDirectoryResolver::findReadableFile($this->dailyLogBasename($dateYmd));
    }

    public function pathForArchive(string $dateYmd): ?string
    {
        return LogDirectoryResolver::findReadableFile($this->archiveBasename($dateYmd));
    }

    public function resolveLogPath(?string $dateYmd = null): ?string
    {
        $date = $dateYmd ?: now()->format('Y-m-d');
        $daily = $this->pathForDailyLog($date);

        if ($daily !== null) {
            return $daily;
        }

        return LogDirectoryResolver::findReadableFile('payments.log');
    }

    /**
     * @return list<array{
     *     date: string,
     *     label: string,
     *     basename: ?string,
     *     size_bytes: ?int,
     *     kind: 'log'|'zip'|'missing',
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
            $zipPath = $this->pathForArchive($date);

            if ($logPath !== null) {
                $basename = $this->dailyLogBasename($date);
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

            if ($zipPath !== null) {
                $basename = $this->archiveBasename($date);
                $entries[] = [
                    'date' => $date,
                    'label' => $this->formatDisplayDate($date),
                    'basename' => $basename,
                    'size_bytes' => @filesize($zipPath) ?: null,
                    'kind' => 'zip',
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
     * @return list<array{
     *     date: string,
     *     label: string,
     *     basename: string,
     *     size_bytes: ?int,
     *     kind: 'zip',
     *     download_url: ?string
     * }>
     */
    public function archivedZipEntries(): array
    {
        $cutoff = now()->subDays(self::RECENT_DAYS)->startOfDay();
        $entries = [];

        foreach (LogDirectoryResolver::globReadable('payments-*.log.zip') as $path) {
            $basename = basename($path);

            if (! preg_match('/^payments-(\d{4}-\d{2}-\d{2})\.log\.zip$/', $basename, $matches)) {
                continue;
            }

            try {
                $fileDate = Carbon::createFromFormat('Y-m-d', $matches[1])->startOfDay();
            } catch (\Throwable) {
                continue;
            }

            if ($fileDate->greaterThanOrEqualTo($cutoff)) {
                continue;
            }

            $entries[] = [
                'date' => $matches[1],
                'label' => $this->formatDisplayDate($matches[1]),
                'basename' => $basename,
                'size_bytes' => @filesize($path) ?: null,
                'kind' => 'zip',
                'download_url' => $this->downloadUrlForBasename($basename),
            ];
        }

        usort($entries, fn (array $a, array $b): int => strcmp($b['date'], $a['date']));

        return $entries;
    }

    public function downloadUrlForBasename(string $basename): ?string
    {
        if ($this->resolvePathByBasename($basename) === null) {
            return null;
        }

        return route('filament.admin.payment-logs.download', ['file' => $basename], absolute: true);
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
        $basename = 'payments.log';
        $path = LogDirectoryResolver::findReadableFile($basename);

        if ($path === null) {
            return null;
        }

        return [
            'label' => 'فایل تجمیعی (payments.log)',
            'basename' => $basename,
            'size_bytes' => @filesize($path) ?: null,
            'kind' => 'log',
            'download_url' => $this->downloadUrlForBasename($basename),
        ];
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

    /**
     * @return list<string> Y-m-d dates (newest first)
     */
    public function availableLogDates(int $daysBack = 30): array
    {
        $dates = [];

        for ($i = 0; $i < $daysBack; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            if ($this->pathForDailyLog($date) !== null || $this->pathForArchive($date) !== null) {
                $dates[] = $date;
            }
        }

        return $dates;
    }

    /**
     * @return array{
     *     lines: list<string>,
     *     truncated: bool,
     *     scanned_files: list<string>,
     *     primary_file: ?string
     * }
     */
    public function searchByTrackingCode(string $trackingCode, ?string $dateYmd = null, int $daySpan = 7): array
    {
        $needle = strtoupper(trim($trackingCode));

        if ($needle === '') {
            return [
                'lines' => [],
                'truncated' => false,
                'scanned_files' => [],
                'primary_file' => null,
            ];
        }

        $files = $this->filesToScan($dateYmd, max(1, min(30, $daySpan)));
        $matches = [];
        $truncated = false;

        foreach ($files as $file) {
            foreach ($this->readMatchingLines($file, $needle) as $line) {
                $matches[] = $line;

                if (count($matches) >= self::MAX_MATCH_LINES) {
                    $truncated = true;
                    break 2;
                }
            }
        }

        return [
            'lines' => $matches,
            'truncated' => $truncated,
            'scanned_files' => $files,
            'primary_file' => $files[0] ?? null,
        ];
    }

    /**
     * @return list<string>
     */
    private function filesToScan(?string $dateYmd, int $daySpan): array
    {
        $files = [];

        if ($dateYmd !== null && $dateYmd !== '') {
            $path = $this->resolveLogPath($dateYmd);
            if ($path !== null) {
                $files[] = $path;
            }

            return $files;
        }

        for ($i = 0; $i < $daySpan; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            $path = $this->pathForDailyLog($date) ?? $this->pathForArchive($date);
            if ($path !== null && ! in_array($path, $files, true)) {
                $files[] = $path;
            }
        }

        return $files;
    }

    /**
     * @return \Generator<int, string>
     */
    private function readMatchingLines(string $path, string $needle): \Generator
    {
        if (str_ends_with($path, '.zip')) {
            return;
        }

        $handle = @fopen($path, 'rb');

        if ($handle === false) {
            return;
        }

        try {
            while (($line = fgets($handle)) !== false) {
                if ($this->lineMatchesNeedle($line, $needle)) {
                    yield rtrim($line, "\r\n");
                }
            }
        } finally {
            fclose($handle);
        }
    }

    private function lineMatchesNeedle(string $line, string $needle): bool
    {
        if (str_contains($line, $needle)) {
            return true;
        }

        return str_contains(strtoupper($line), $needle);
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

    public function isDownloadableBasename(string $basename): bool
    {
        return (bool) preg_match('/^payments(-\d{4}-\d{2}-\d{2})?\.log(\.zip)?$/', basename($basename));
    }

    public function resolvePathByBasename(string $basename): ?string
    {
        $basename = basename($basename);

        if (! $this->isDownloadableBasename($basename)) {
            return null;
        }

        $path = LogDirectoryResolver::findReadableFile($basename);

        if ($path === null || ! LogDirectoryResolver::pathIsAllowedLogFile($path)) {
            return null;
        }

        return $path;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function directoryDiagnostics(): array
    {
        return LogDirectoryResolver::directoryDiagnostics();
    }

    public function mimeTypeForBasename(string $basename): string
    {
        return str_ends_with($basename, '.zip')
            ? 'application/zip'
            : 'text/plain; charset=UTF-8';
    }
}
