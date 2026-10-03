<?php

namespace App\Services\Payment;

use Carbon\Carbon;

class PaymentLogReader
{
    private const MAX_MATCH_LINES = 800;

    public function logDirectory(): string
    {
        return storage_path('logs');
    }

    public function resolveLogPath(?string $dateYmd = null): ?string
    {
        $date = $dateYmd ?: now()->format('Y-m-d');
        $daily = $this->logDirectory().'/payments-'.$date.'.log';

        if (is_readable($daily)) {
            return $daily;
        }

        $single = $this->logDirectory().'/payments.log';

        return is_readable($single) ? $single : null;
    }

    /**
     * @return list<string> Y-m-d dates (newest first)
     */
    public function availableLogDates(int $daysBack = 30): array
    {
        $dates = [];

        for ($i = 0; $i < $daysBack; $i++) {
            $date = now()->subDays($i)->format('Y-m-d');
            if ($this->resolveLogPath($date) !== null) {
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
            $path = $this->resolveLogPath($date);
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
            return '۷ روز اخیر';
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $dateYmd)->format('Y/m/d');
        } catch (\Throwable) {
            return $dateYmd;
        }
    }
}
