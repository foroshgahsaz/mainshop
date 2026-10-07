<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class StoragePermissionFixer
{
    /** @return list<string> */
    public static function requiredPaths(): array
    {
        $publicRoot = rtrim((string) config('filesystems.disks.public.root'), '/');

        return array_values(array_unique(array_filter([
            $publicRoot,
            ShopStoragePaths::logsDirectory(),
            ShopStoragePaths::backupDirectory(),
            Storage::disk('livewire-tmp')->path((string) config('livewire.temporary_file_upload.directory', 'livewire-tmp')),
            Storage::disk('public')->path('products'),
            Storage::disk('public')->path('brands'),
            Storage::disk('public')->path('sliders'),
            Storage::disk('public')->path('categories'),
            Storage::disk('public')->path('posts'),
            Storage::disk('public')->path('seo'),
            Storage::disk('public')->path('pages'),
            Storage::disk('public')->path('thumbs'),
            Storage::disk('public')->path('.thumbs'),
        ])));
    }

    public static function runningAsRoot(): bool
    {
        return function_exists('posix_geteuid') && posix_geteuid() === 0;
    }

    public static function webUser(): string
    {
        $configured = config('shop.storage.web_user');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $inferred = self::inferOwnerFromReferencePath();

        return $inferred['user'] ?? 'www-data';
    }

    public static function webGroup(): string
    {
        $configured = config('shop.storage.web_group');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        $inferred = self::inferOwnerFromReferencePath();

        return $inferred['group'] ?? self::webUser();
    }

    /**
     * @return array{user: string, group: string, uid: int, gid: int}|null
     */
    public static function inferOwnerFromReferencePath(): ?array
    {
        $candidates = array_filter([
            Storage::disk('public')->path('products'),
            rtrim((string) config('filesystems.disks.public.root'), '/\\'),
            '/data/products',
        ]);

        foreach ($candidates as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $uid = @fileowner($path);
            $gid = @filegroup($path);

            if ($uid === false || $gid === false || (int) $uid === 0) {
                continue;
            }

            $uid = (int) $uid;
            $gid = (int) $gid;
            $user = self::posixNameForUid($uid);
            $group = self::posixNameForGid($gid);

            return [
                'user' => $user ?? (string) $uid,
                'group' => $group ?? (string) $gid,
                'uid' => $uid,
                'gid' => $gid,
            ];
        }

        return null;
    }

    public static function webOwnerLabel(): string
    {
        $inferred = self::inferOwnerFromReferencePath();

        if ($inferred === null) {
            return self::webUser().':'.self::webGroup().' (configured fallback)';
        }

        return $inferred['user'].':'.$inferred['group'].' (uid '.$inferred['uid'].' gid '.$inferred['gid'].')';
    }

    public static function fix(): void
    {
        foreach (self::requiredPaths() as $path) {
            self::ensureWritableDirectory($path);
        }
    }

    public static function ensureWritableDirectory(string $path): void
    {
        $path = rtrim($path, '/\\');
        if ($path === '') {
            return;
        }

        $runningAsRoot = self::runningAsRoot();
        $owner = self::inferOwnerFromReferencePath();

        if (! is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        if (! is_dir($path)) {
            return;
        }

        if ($runningAsRoot) {
            if ($owner !== null) {
                @chown($path, $owner['uid']);
                @chgrp($path, $owner['gid']);
            } else {
                @chown($path, self::webUser());
                @chgrp($path, self::webGroup());
            }
        }

        @chmod($path, 0775);

        if (! self::isDirectoryWritable($path) && $runningAsRoot) {
            @chmod($path, 0777);
        }
    }

    public static function isDirectoryWritable(string $absoluteDir): bool
    {
        if (! is_dir($absoluteDir)) {
            return false;
        }

        if (is_writable($absoluteDir)) {
            return true;
        }

        $probe = $absoluteDir.'/.write-probe-'.uniqid('', true);

        if (@file_put_contents($probe, '1') !== false) {
            @unlink($probe);

            return true;
        }

        return false;
    }

    private static function posixNameForUid(int $uid): ?string
    {
        if (! function_exists('posix_getpwuid')) {
            return null;
        }

        $info = posix_getpwuid($uid);

        return is_array($info) ? ($info['name'] ?? null) : null;
    }

    private static function posixNameForGid(int $gid): ?string
    {
        if (! function_exists('posix_getgrgid')) {
            return null;
        }

        $info = posix_getgrgid($gid);

        return is_array($info) ? ($info['name'] ?? null) : null;
    }
}
