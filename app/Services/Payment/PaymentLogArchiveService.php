<?php

namespace App\Services\Payment;

use Carbon\Carbon;
use ZipArchive;

class PaymentLogArchiveService
{
    public function zipAvailable(): bool
    {
        return class_exists(ZipArchive::class) && extension_loaded('zip');
    }

    public function __construct(
        protected PaymentLogReader $reader,
    ) {}

    public function keepDays(): int
    {
        return PaymentLogReader::RECENT_DAYS;
    }

    /**
     * Zip daily payment logs older than the retention window and remove the plain .log file.
     */
    public function archiveLogsOlderThanRetention(): int
    {
        if (! $this->zipAvailable()) {
            return 0;
        }

        $archived = 0;
        $cutoff = now()->subDays($this->keepDays())->startOfDay();
        $directory = $this->reader->logDirectory();

        foreach (glob($directory.DIRECTORY_SEPARATOR.'payments-*.log') ?: [] as $path) {
            $basename = basename($path);

            if (! preg_match('/^payments-(\d{4}-\d{2}-\d{2})\.log$/', $basename, $matches)) {
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

            if ($this->archiveLogFile($path, $matches[1])) {
                $archived++;
            }
        }

        return $archived;
    }

    protected function archiveLogFile(string $logPath, string $dateYmd): bool
    {
        if (! is_readable($logPath)) {
            return false;
        }

        $zipBasename = $this->reader->archiveBasename($dateYmd);
        $zipPath = $this->reader->logDirectory().DIRECTORY_SEPARATOR.$zipBasename;

        if (is_file($zipPath)) {
            @unlink($logPath);

            return true;
        }

        if (! $this->zipAvailable()) {
            return false;
        }

        $zip = new ZipArchive;
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($opened !== true) {
            return false;
        }

        $zip->addFile($logPath, $this->reader->dailyLogBasename($dateYmd));
        $zip->close();

        if (! is_file($zipPath)) {
            return false;
        }

        @unlink($logPath);

        return true;
    }
}
