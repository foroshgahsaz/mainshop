<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;

class DiagnoseUploads extends Command
{
    protected $signature = 'shop:diagnose-uploads';

    protected $description = 'Report Livewire/Filament upload disk paths and writability (Runflare multi-pod)';

    public function handle(): int
    {
        $this->info('Upload diagnostics');
        $this->newLine();

        $publicDisk = Storage::disk('public');
        $tempDiskName = config('livewire.temporary_file_upload.disk', 'public');
        $tempDisk = Storage::disk($tempDiskName);
        $tempDirectory = FileUploadConfiguration::directory();

        $rows = [
            ['public disk root', (string) config('filesystems.disks.public.root')],
            ['public disk url', (string) config('filesystems.disks.public.url')],
            ['livewire temp disk', $tempDiskName],
            ['livewire temp root', (string) config("filesystems.disks.{$tempDiskName}.root")],
            ['livewire temp directory', $tempDirectory],
            ['livewire temp full path', $tempDisk->path($tempDirectory)],
            ['override loaded', $this->overridePath()],
        ];

        $this->table(['Setting', 'Value'], $rows);

        $this->newLine();
        $this->line('Writable checks:');
        $this->line('  public/products: '.($this->checkWritable($publicDisk, 'products') ? 'OK' : 'FAIL'));
        $this->line('  livewire temp: '.($this->checkWritable($tempDisk, $tempDirectory) ? 'OK' : 'FAIL'));

        $testFile = $tempDirectory.'/diagnose-'.uniqid('', true).'.txt';
        try {
            $tempDisk->put($testFile, 'ok');
            $exists = $tempDisk->exists($testFile);
            $tempDisk->delete($testFile);
            $this->line('  temp write/read: '.($exists ? 'OK' : 'FAIL'));
        } catch (\Throwable $exception) {
            $this->error('  temp write/read: FAIL — '.$exception->getMessage());
        }

        $this->newLine();
        $this->line('Sample public URL:');
        $this->line('  '.Storage::disk('public')->url('products/example.jpg'));

        $symlink = public_path('storage');
        $this->newLine();
        $this->line('public/storage symlink: '.(is_link($symlink) ? 'OK → '.readlink($symlink) : (is_dir($symlink) ? 'directory (not symlink)' : 'missing (OK on Runflare /data)')));

        $this->newLine();
        $this->comment('Runflare: FILESYSTEM_PUBLIC_ROOT and LIVEWIRE_TEMP_ROOT must be on the same persistent volume (e.g. /data).');
        $this->comment('Image URLs must use Storage::disk(\'public\')->url() — not hardcoded /storage paths.');

        return self::SUCCESS;
    }

    protected function overridePath(): string
    {
        $ref = new \ReflectionClass(\Livewire\Features\SupportFileUploads\TemporaryUploadedFile::class);

        return $ref->getFileName();
    }

    protected function checkWritable($disk, string $path): bool
    {
        try {
            $disk->makeDirectory($path);

            return is_writable($disk->path($path));
        } catch (\Throwable) {
            return false;
        }
    }
}
