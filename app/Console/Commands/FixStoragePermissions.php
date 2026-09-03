<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixStoragePermissions extends Command
{
    protected $signature = 'shop:fix-storage-permissions';

    protected $description = 'Ensure /data upload directories exist and are writable by www-data (Runflare deploy hook)';

    public function handle(): int
    {
        $webUser = 'www-data';
        $paths = array_values(array_unique(array_filter([
            (string) config('filesystems.disks.public.root'),
            Storage::disk('livewire-tmp')->path((string) config('livewire.temporary_file_upload.directory', 'livewire-tmp')),
            Storage::disk('public')->path('products'),
        ])));

        $runningAsRoot = function_exists('posix_geteuid') && posix_geteuid() === 0;

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                if (! @mkdir($path, 0775, true) && ! is_dir($path)) {
                    $this->error("Could not create: {$path}");

                    continue;
                }
            }

            if ($runningAsRoot) {
                @chown($path, $webUser);
                @chgrp($path, $webUser);
            }

            @chmod($path, 0775);

            $writable = is_writable($path) ? 'writable' : 'NOT writable';
            $this->line("{$path} — {$writable}");
        }

        if (! $runningAsRoot) {
            $this->warn('Run as root in deploy hook for chown (e.g. kubectl exec as root).');
        }

        return self::SUCCESS;
    }
}
