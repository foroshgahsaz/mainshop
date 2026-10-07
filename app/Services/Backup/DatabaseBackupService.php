<?php

namespace App\Services\Backup;

use App\Support\StoragePermissionFixer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public const KIND_MANUAL = 'manual';

    public const KIND_SCHEDULED = 'scheduled';

    public function backupDirectory(): string
    {
        return (string) config('backup.database.directory');
    }

    public function keepCount(): int
    {
        return max(1, (int) config('backup.database.keep', 2));
    }

    public function create(bool $manual = false): string
    {
        $this->ensureDirectory();

        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");

        $basename = $this->buildBasename($driver, $manual ? self::KIND_MANUAL : self::KIND_SCHEDULED);
        $targetPath = $this->backupDirectory().DIRECTORY_SEPARATOR.$basename;

        match ($driver) {
            'mysql', 'mariadb' => $this->dumpMysql($connection, $targetPath),
            'sqlite' => $this->dumpSqlite($connection, $targetPath),
            default => throw new RuntimeException("پشتیبانی از درایور دیتابیس «{$driver}» برای بک‌آپ وجود ندارد."),
        };

        if (! is_readable($targetPath)) {
            throw new RuntimeException('فایل بک‌آپ ایجاد نشد یا قابل خواندن نیست.');
        }

        $this->pruneExcessBackups();

        return $targetPath;
    }

    /**
     * @return list<array{
     *     basename: string,
     *     label: string,
     *     kind: string,
     *     kind_label: string,
     *     size_bytes: int,
     *     created_at: string,
     *     download_url: string
     * }>
     */
    public function listBackups(int $limit = 2): array
    {
        $this->ensureDirectory();

        $files = collect(File::files($this->backupDirectory()))
            ->filter(fn (\SplFileInfo $file) => $this->isBackupBasename($file->getFilename()))
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->take(max(1, $limit))
            ->values();

        return $files->map(function (\SplFileInfo $file): array {
            $basename = $file->getFilename();
            $kind = $this->kindFromBasename($basename);

            return [
                'basename' => $basename,
                'label' => $this->labelFromBasename($basename),
                'kind' => $kind,
                'kind_label' => $this->kindLabel($kind),
                'size_bytes' => (int) $file->getSize(),
                'created_at' => Carbon::createFromTimestamp($file->getMTime())->timezone($this->displayTimezone())->shopJalali(),
                'download_url' => $this->downloadUrl($basename),
            ];
        })->all();
    }

    public function resolvePathByBasename(string $basename): ?string
    {
        $basename = basename($basename);
        if (! $this->isBackupBasename($basename)) {
            return null;
        }

        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.$basename;

        return is_readable($path) ? $path : null;
    }

    public function downloadUrl(string $basename): string
    {
        return route('filament.admin.database-backups.download', ['file' => basename($basename)], absolute: true);
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
            return number_format($bytes / 1024, 1).' KB';
        }

        if ($bytes < 1024 * 1024 * 1024) {
            return number_format($bytes / (1024 * 1024), 1).' MB';
        }

        return number_format($bytes / (1024 * 1024 * 1024), 2).' GB';
    }

    public function mimeTypeForBasename(string $basename): string
    {
        if (Str::endsWith($basename, '.gz')) {
            return 'application/gzip';
        }

        if (Str::endsWith($basename, '.sql')) {
            return 'application/sql';
        }

        return 'application/octet-stream';
    }

    protected function ensureDirectory(): void
    {
        $dir = $this->backupDirectory();
        StoragePermissionFixer::ensureWritableDirectory($dir);

        if (! StoragePermissionFixer::isDirectoryWritable($dir)) {
            throw new RuntimeException(
                'پوشهٔ بک‌آپ قابل نوشتن نیست ('.$dir.'). مالک باید مثل /data/products باشد (Runflare: xfs:xfs). روی سرور (root): chown -R xfs:xfs '.$dir.' && php artisan shop:fix-storage-permissions'
            );
        }
    }

    protected function buildBasename(string $driver, string $kind): string
    {
        $timestamp = now()->timezone($this->displayTimezone())->format('Y-m-d-His');
        $extension = in_array($driver, ['mysql', 'mariadb'], true) ? 'sql.gz' : 'sqlite.gz';

        return "database-{$timestamp}-{$kind}.{$extension}";
    }

    protected function isBackupBasename(string $basename): bool
    {
        return (bool) preg_match('/^database-\d{4}-\d{2}-\d{2}-\d{6}-(manual|scheduled)\.(sql\.gz|sqlite\.gz)$/', $basename);
    }

    protected function kindFromBasename(string $basename): string
    {
        if (str_contains($basename, '-manual.')) {
            return self::KIND_MANUAL;
        }

        return self::KIND_SCHEDULED;
    }

    protected function kindLabel(string $kind): string
    {
        return match ($kind) {
            self::KIND_MANUAL => 'دستی',
            default => 'خودکار (عصر)',
        };
    }

    protected function labelFromBasename(string $basename): string
    {
        if (! preg_match('/^database-(\d{4})-(\d{2})-(\d{2})-(\d{2})(\d{2})(\d{2})-/', $basename, $m)) {
            return $basename;
        }

        return sprintf('%s/%s/%s %s:%s:%s', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
    }

    protected function displayTimezone(): string
    {
        return (string) config('backup.database.schedule_timezone', 'Asia/Tehran');
    }

    protected function pruneExcessBackups(): void
    {
        $keep = $this->keepCount();
        $files = collect(File::files($this->backupDirectory()))
            ->filter(fn (\SplFileInfo $file) => $this->isBackupBasename($file->getFilename()))
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->values();

        foreach ($files->slice($keep) as $file) {
            @unlink($file->getPathname());
        }
    }

    protected function dumpMysql(string $connection, string $targetPath): void
    {
        $config = Config::get("database.connections.{$connection}");
        $database = (string) ($config['database'] ?? '');
        $username = (string) ($config['username'] ?? '');

        if ($database === '' || $username === '') {
            throw new RuntimeException('تنظیمات اتصال MySQL برای بک‌آپ ناقص است.');
        }

        $binary = $this->resolveMysqldumpBinary();
        $usePhpFallback = filter_var(config('backup.database.php_fallback', true), FILTER_VALIDATE_BOOLEAN);

        if ($binary !== null) {
            try {
                $this->dumpMysqlWithBinary($binary, $connection, $targetPath);

                return;
            } catch (RuntimeException $e) {
                if (! $usePhpFallback) {
                    throw $e;
                }
            }
        }

        if (! $usePhpFallback) {
            throw new RuntimeException(
                $binary === null
                    ? 'دستور mysqldump روی سرور پیدا نشد. مسیر را در DB_BACKUP_MYSQLDUMP_PATH تنظیم کنید یا DB_BACKUP_PHP_FALLBACK=true بگذارید.'
                    : 'mysqldump و fallback PHP هر دو ناموفق بودند.'
            );
        }

        app(MysqlPhpDumper::class)->dumpConnectionToGzipFile($connection, $targetPath);
    }

    protected function resolveMysqldumpBinary(): ?string
    {
        $candidates = array_values(array_filter([
            (string) config('backup.database.mysqldump_path'),
            'mysqldump',
            'mariadb-dump',
            '/usr/bin/mysqldump',
            '/usr/bin/mariadb-dump',
        ]));

        foreach ($candidates as $candidate) {
            if (str_contains($candidate, '/')) {
                if (is_executable($candidate)) {
                    return $candidate;
                }

                continue;
            }

            if ($this->commandExistsOnPath($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function commandExistsOnPath(string $command): bool
    {
        $process = new Process(['sh', '-c', 'command -v '.escapeshellarg($command).' 2>/dev/null']);
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }

    protected function dumpMysqlWithBinary(string $binary, string $connection, string $targetPath): void
    {
        $config = Config::get("database.connections.{$connection}");
        $database = (string) ($config['database'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? '3306');
        $username = (string) ($config['username'] ?? '');
        $password = (string) ($config['password'] ?? '');
        $socket = (string) ($config['unix_socket'] ?? '');

        $command = [
            $binary,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            '--column-statistics=0',
            '--no-tablespaces',
        ];

        if ($socket !== '') {
            $command[] = '--socket='.$socket;
        } else {
            $command[] = '--host='.$host;
            $command[] = '--port='.$port;
        }

        $command[] = '--user='.$username;
        $command[] = $database;

        $process = new Process($command, null, ['MYSQL_PWD' => $password]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $detail = trim($process->getErrorOutput()."\n".$process->getOutput());
            if ($detail === '') {
                $detail = 'خروجی خالی (کد خروج: '.$process->getExitCode().'). احتمالاً mysqldump نصب نیست یا به دیتابیس دسترسی ندارد.';
            }

            throw new RuntimeException('اجرای mysqldump ناموفق بود: '.$detail);
        }

        $output = $process->getOutput();
        if ($output === '') {
            throw new RuntimeException('اجرای mysqldump خروجی تولید نکرد.');
        }

        $gz = gzencode($output, 6);
        if ($gz === false) {
            throw new RuntimeException('فشرده‌سازی بک‌آپ MySQL ناموفق بود.');
        }

        if (file_put_contents($targetPath, $gz) === false) {
            throw new RuntimeException('ذخیره فایل بک‌آپ MySQL ناموفق بود.');
        }
    }

    protected function dumpSqlite(string $connection, string $targetPath): void
    {
        $databasePath = (string) config("database.connections.{$connection}.database");
        if ($databasePath === '' || ! is_readable($databasePath)) {
            throw new RuntimeException('فایل SQLite دیتابیس پیدا نشد یا قابل خواندن نیست.');
        }

        $contents = file_get_contents($databasePath);
        if ($contents === false) {
            throw new RuntimeException('خواندن فایل SQLite ناموفق بود.');
        }

        $gz = gzencode($contents, 6);
        if ($gz === false) {
            throw new RuntimeException('فشرده‌سازی بک‌آپ SQLite ناموفق بود.');
        }

        if (file_put_contents($targetPath, $gz) === false) {
            throw new RuntimeException('ذخیره فایل بک‌آپ SQLite ناموفق بود.');
        }
    }
}
