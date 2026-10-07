<?php

namespace App\Support;

/**
 * Laravel may write logs under the configured directory while older files
 * remain in storage/logs or /var/www/storage/logs (Runflare). Admin viewers
 * search all known locations so operators still see files after path changes.
 */
class LogDirectoryResolver
{
    /**
     * @return list<string> absolute directories, primary first
     */
    public static function searchDirectories(): array
    {
        $primary = rtrim(ShopStoragePaths::logsDirectory(), '/\\');
        $candidates = [
            $primary,
            rtrim(storage_path('logs'), '/\\'),
            '/var/www/storage/logs',
            '/storage/logs',
        ];

        $out = [];

        foreach ($candidates as $dir) {
            if ($dir === '' || in_array($dir, $out, true)) {
                continue;
            }

            $out[] = $dir;
        }

        return $out;
    }

    public static function findReadableFile(string $basename): ?string
    {
        $basename = basename($basename);

        if ($basename === '' || $basename === '.' || $basename === '..') {
            return null;
        }

        foreach (self::searchDirectories() as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$basename;

            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return list<string> absolute paths, newest modification time first
     */
    public static function globReadable(string $filePattern): array
    {
        $filePattern = basename($filePattern);
        $byBasename = [];

        foreach (self::searchDirectories() as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            foreach (glob($dir.DIRECTORY_SEPARATOR.$filePattern) ?: [] as $path) {
                if (! is_readable($path)) {
                    continue;
                }

                $name = basename($path);

                if (! isset($byBasename[$name])) {
                    $byBasename[$name] = $path;
                }
            }
        }

        $paths = array_values($byBasename);

        usort($paths, fn (string $a, string $b): int => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));

        return $paths;
    }

    public static function pathIsAllowedLogFile(string $absolutePath): bool
    {
        $normalizedFile = self::normalizePath($absolutePath);

        foreach (self::searchDirectories() as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $normalizedDir = self::normalizePath($dir);

            if ($normalizedFile === $normalizedDir || str_starts_with($normalizedFile, $normalizedDir.'/')) {
                return is_file($absolutePath);
            }
        }

        return false;
    }

    /**
     * @return list<array{
     *     path: string,
     *     exists: bool,
     *     readable: bool,
     *     writable: bool,
     *     log_file_count: int,
     *     is_primary: bool
     * }>
     */
    public static function directoryDiagnostics(): array
    {
        $primary = rtrim(ShopStoragePaths::logsDirectory(), '/\\');
        $rows = [];

        foreach (self::searchDirectories() as $dir) {
            $exists = is_dir($dir);
            $rows[] = [
                'path' => $dir,
                'exists' => $exists,
                'readable' => $exists && is_readable($dir),
                'writable' => $exists && is_writable($dir),
                'log_file_count' => $exists ? self::countLogLikeFiles($dir) : 0,
                'is_primary' => $dir === $primary,
            ];
        }

        return $rows;
    }

    protected static function countLogLikeFiles(string $dir): int
    {
        $count = 0;

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$entry;

            if (! is_file($path)) {
                continue;
            }

            if (preg_match('/\.(log|zip)$/i', $entry)) {
                $count++;
            }
        }

        return $count;
    }

    protected static function normalizePath(string $path): string
    {
        return rtrim(str_replace('\\', '/', $path), '/');
    }
}
