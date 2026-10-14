<?php

namespace App\Services\Backup;

use App\Support\StoragePermissionFixer;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;
use ZipArchive;

class FilesBackupService
{
    public const KIND_MANUAL = 'manual';

    public const KIND_SCHEDULED = 'scheduled';

    public function backupDirectory(): string
    {
        return (string) config('backup.files.directory');
    }

    public function keepCount(): int
    {
        return max(1, (int) config('backup.files.keep', 1));
    }

    public function create(bool $manual = false): string
    {
        $this->ensureDirectory();

        $roots = $this->sourceRoots();
        if ($roots === []) {
            throw new RuntimeException('مسیر فایل برای بک‌آپ پیدا نشد (FILESYSTEM_PUBLIC_ROOT یا storage/app/public).');
        }

        $basename = $this->buildBasename($manual ? self::KIND_MANUAL : self::KIND_SCHEDULED);
        $targetPath = $this->backupDirectory().DIRECTORY_SEPARATOR.$basename;

        if ($this->canUseTar()) {
            $this->createWithTar($roots, $targetPath);
        } else {
            $this->createWithZip($roots, $targetPath);
        }

        if (! is_readable($targetPath) || filesize($targetPath) === 0) {
            throw new RuntimeException('فایل بک‌آپ فایل‌ها ایجاد نشد یا خالی است.');
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
    public function listBackups(int $limit = 1): array
    {
        $this->ensureDirectory();

        $files = collect(File::files($this->backupDirectory()))
            ->filter(fn (SplFileInfo $file) => $this->isBackupBasename($file->getFilename()))
            ->sortByDesc(fn (SplFileInfo $file) => $file->getMTime())
            ->take(max(1, $limit))
            ->values();

        return $files->map(function (SplFileInfo $file): array {
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
        return route('filament.admin.database-backups.download', [
            'file' => basename($basename),
            'type' => 'files',
        ], absolute: true);
    }

    public function mimeTypeForBasename(string $basename): string
    {
        if (Str::endsWith($basename, '.tar.gz') || Str::endsWith($basename, '.gz')) {
            return 'application/gzip';
        }

        if (Str::endsWith($basename, '.zip')) {
            return 'application/zip';
        }

        return 'application/octet-stream';
    }

    public function formatFileSize(?int $bytes): string
    {
        return app(DatabaseBackupService::class)->formatFileSize($bytes);
    }

    /** @return list<string> */
    public function sourceRoots(): array
    {
        $configured = config('backup.files.roots');
        if (is_array($configured) && $configured !== []) {
            return array_values(array_filter(array_map(
                fn ($path) => is_string($path) && is_dir($path) ? rtrim($path, '/\\') : null,
                $configured,
            )));
        }

        $publicRoot = rtrim((string) config('filesystems.disks.public.root', ''), '/\\');
        if ($publicRoot !== '' && is_dir($publicRoot)) {
            return [$publicRoot];
        }

        return [];
    }

    /** @return list<string> */
    protected function excludeDirNames(): array
    {
        $names = config('backup.files.exclude_dir_names');

        return is_array($names) ? array_values(array_filter($names, 'is_string')) : [];
    }

    protected function ensureDirectory(): void
    {
        $dir = $this->backupDirectory();
        StoragePermissionFixer::ensureWritableDirectory($dir);

        if (! StoragePermissionFixer::isDirectoryWritable($dir)) {
            throw new RuntimeException(
                'پوشهٔ بک‌آپ فایل‌ها قابل نوشتن نیست ('.$dir.'). روی سرور: php artisan shop:fix-storage-permissions'
            );
        }
    }

    protected function buildBasename(string $kind): string
    {
        $timestamp = now()->timezone($this->displayTimezone())->format('Y-m-d-His');
        $extension = $this->canUseTar() ? 'tar.gz' : 'zip';

        return "files-{$timestamp}-{$kind}.{$extension}";
    }

    protected function isBackupBasename(string $basename): bool
    {
        return (bool) preg_match('/^files-\d{4}-\d{2}-\d{2}-\d{6}-(manual|scheduled)\.(tar\.gz|zip)$/', $basename);
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
            default => 'خودکار (روزانه)',
        };
    }

    protected function labelFromBasename(string $basename): string
    {
        if (! preg_match('/^files-(\d{4})-(\d{2})-(\d{2})-(\d{2})(\d{2})(\d{2})-/', $basename, $m)) {
            return $basename;
        }

        return sprintf('%s/%s/%s %s:%s:%s', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
    }

    protected function displayTimezone(): string
    {
        return (string) config('backup.files.schedule_timezone', 'Asia/Tehran');
    }

    protected function pruneExcessBackups(): void
    {
        $keep = $this->keepCount();
        $files = collect(File::files($this->backupDirectory()))
            ->filter(fn (SplFileInfo $file) => $this->isBackupBasename($file->getFilename()))
            ->sortByDesc(fn (SplFileInfo $file) => $file->getMTime())
            ->values();

        foreach ($files->slice($keep) as $file) {
            @unlink($file->getPathname());
        }
    }

    protected function canUseTar(): bool
    {
        if (! filter_var(config('backup.files.prefer_tar', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        $process = new Process(['sh', '-c', 'command -v tar 2>/dev/null']);
        $process->run();

        return $process->isSuccessful() && trim($process->getOutput()) !== '';
    }

    /**
     * @param  list<string>  $roots
     */
    protected function createWithTar(array $roots, string $targetPath): void
    {
        if (count($roots) > 1) {
            $zipTarget = (string) preg_replace('/\.tar\.gz$/', '.zip', $targetPath);
            $this->createWithZip($roots, $zipTarget);

            return;
        }

        $root = $roots[0];
        $command = ['tar', '-czf', $targetPath, '-C', $root];

        foreach ($this->excludeDirNames() as $name) {
            $command[] = '--exclude=./'.$name;
        }

        $command[] = '.';

        $process = new Process($command);
        $process->setTimeout((int) config('backup.files.process_timeout', 3600));
        $process->run();

        if (! $process->isSuccessful()) {
            $detail = trim($process->getErrorOutput()."\n".$process->getOutput());
            throw new RuntimeException('اجرای tar برای بک‌آپ فایل‌ها ناموفق بود: '.($detail ?: 'بدون جزئیات'));
        }
    }

    /**
     * @param  list<string>  $roots
     */
    protected function createWithZip(array $roots, string $targetPath): void
    {
        if (Str::endsWith($targetPath, '.tar.gz')) {
            $targetPath = (string) preg_replace('/\.tar\.gz$/', '.zip', $targetPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($targetPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('باز کردن فایل zip برای بک‌آپ ناموفق بود.');
        }

        foreach ($roots as $root) {
            $prefix = basename($root) ?: 'files';
            $this->addDirectoryToZip($zip, $root, $prefix, $this->excludeDirNames());
        }

        $zip->close();
    }

    /**
     * @param  list<string>  $excludeDirNames
     */
    protected function addDirectoryToZip(ZipArchive $zip, string $directory, string $zipPrefix, array $excludeDirNames): void
    {
        $directory = rtrim($directory, '/\\');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            /** @var SplFileInfo $item */
            $relative = ltrim(str_replace($directory, '', $item->getPathname()), '/\\');
            if ($relative === '') {
                continue;
            }

            $topSegment = explode(DIRECTORY_SEPARATOR, str_replace('\\', '/', $relative))[0];
            if (in_array($topSegment, $excludeDirNames, true)) {
                continue;
            }

            $localName = $zipPrefix.'/'.$relative;

            if ($item->isDir()) {
                $zip->addEmptyDir($localName);
            } else {
                $zip->addFile($item->getPathname(), $localName);
            }
        }
    }
}
