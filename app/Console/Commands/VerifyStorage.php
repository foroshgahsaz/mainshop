<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class VerifyStorage extends Command
{
    protected $signature = 'shop:verify-storage';

    protected $description = 'Verify public disk, /data volume, and legacy storage paths (multi-pod diagnostics)';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $publicRoot = rtrim((string) config('filesystems.disks.public.root'), '/');
        $legacyRoot = rtrim(storage_path('app/public'), '/');

        $this->info('Storage verification');
        $this->table(['Key', 'Value'], [
            ['hostname', (string) gethostname()],
            ['public disk root', $publicRoot],
            ['legacy root', $legacyRoot],
            ['roots equal', $publicRoot === $legacyRoot ? 'yes (sync not needed)' : 'no'],
            ['public url', (string) config('filesystems.disks.public.url')],
        ]);

        $this->newLine();
        $this->line('Counts via Laravel Storage (public disk):');

        foreach (['products', 'categories', 'sliders', 'posts', 'settings', 'seo', 'brands'] as $folder) {
            $count = $this->safeCount($disk, $folder);
            $this->line("  {$folder}/: {$count}");
        }

        $totalPublic = $this->safeCount($disk, '');
        $this->line("  ALL on public disk: {$totalPublic}");

        $this->newLine();
        $this->line('Counts via filesystem:');
        $this->line('  /data (recursive): '.$this->countPath('/data'));
        $this->line('  /data/products: '.$this->countPath('/data/products'));
        $this->line('  legacy storage/app/public: '.$this->countPath($legacyRoot));

        $this->newLine();
        $this->line('Sample files on public disk (max 15):');
        $samples = collect($disk->allFiles())
            ->take(15)
            ->values();

        if ($samples->isEmpty()) {
            $this->warn("  (none — {$publicRoot} is empty on THIS pod)");
        } else {
            foreach ($samples as $path) {
                $this->line('  '.$path.' ('.$disk->size($path).' bytes)');
            }
        }

        if ($publicRoot !== $legacyRoot) {
            $legacyCount = $this->countPath($legacyRoot);
            $publicCount = (int) $totalPublic;

            $this->newLine();
            if ($legacyCount > 0 && $publicCount === 0) {
                $this->error('Legacy has files but public disk is empty on this pod.');

                if ($publicRoot === '/data') {
                    $this->newLine();
                    $this->warn('Liara (or similar): persistent disk is usually mounted at storage/app/public, not /data.');
                    $this->line('Recommended fix — remove from production .env:');
                    $this->line('  FILESYSTEM_PUBLIC_ROOT=/data');
                    $this->line('  LIVEWIRE_TEMP_ROOT=/data');
                    $this->line('  LIVEWIRE_TEMP_DISK=livewire-tmp  (optional)');
                    $this->line('Keep FILESYSTEM_PUBLIC_URL if you serve files via /data route.');
                    $this->line('Then: php artisan config:clear');
                    $this->line('Re-run this command — roots equal should be "yes" and products should be > 0.');
                    $this->newLine();
                    $this->comment('Runflare / K8s with /data volume: instead run shop:sync-public-storage, then fix-storage-permissions, then import-existing-media.');
                } else {
                    $this->line('Run: php artisan shop:sync-public-storage');
                    $this->line('Then: php artisan shop:fix-storage-permissions');
                    $this->line('Then: php artisan shop:import-existing-media');
                }
            } elseif ($legacyCount > 0 && $publicCount > 0) {
                $this->comment('Both legacy and public disk have files. Run shop:restore-public-files for DB paths missing on /data.');
            }
        }

        if ($publicRoot === '/data' && $this->countPath('/data') === '0' && $this->countPath($legacyRoot) !== '0') {
            $this->newLine();
            $this->warn('After each deploy, /data is wiped unless it is a shared persistent volume.');
            $this->warn('Runflare/K8s: mount the SAME volume to /data on every pod, then shop:sync-public-storage.');
            $this->warn('Liara: do NOT use /data — use default storage/app/public (remove FILESYSTEM_PUBLIC_ROOT from .env).');
        }

        return self::SUCCESS;
    }

    protected function safeCount($disk, string $folder): string
    {
        try {
            if ($folder === '') {
                return (string) count($disk->allFiles());
            }

            if (! $disk->exists($folder)) {
                return '0 (dir missing)';
            }

            return (string) count($disk->allFiles($folder));
        } catch (\Throwable $exception) {
            return 'error: '.$exception->getMessage();
        }
    }

    protected function countPath(string $path): string
    {
        if (! is_dir($path)) {
            return 'missing';
        }

        $count = 0;

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $count++;
                }
            }
        } catch (\Throwable) {
            return 'error';
        }

        return (string) $count;
    }
}
