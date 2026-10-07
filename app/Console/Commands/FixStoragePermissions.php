<?php

namespace App\Console\Commands;

use App\Support\StoragePermissionFixer;
use Illuminate\Console\Command;

class FixStoragePermissions extends Command
{
    protected $signature = 'shop:fix-storage-permissions';

    protected $description = 'Ensure /data upload, log, and backup dirs exist and match PHP user (Runflare: xfs)';

    public function handle(): int
    {
        StoragePermissionFixer::fix();

        $this->line('Target owner: '.StoragePermissionFixer::webUser().':'.StoragePermissionFixer::webGroup());

        foreach (StoragePermissionFixer::requiredPaths() as $path) {
            $writable = StoragePermissionFixer::isDirectoryWritable($path) ? 'writable' : 'NOT writable';
            $this->line("{$path} — {$writable}");
        }

        if (! StoragePermissionFixer::runningAsRoot()) {
            $this->warn('Run as root in deploy hook for chown (e.g. kubectl exec as root).');
        }

        return self::SUCCESS;
    }
}
