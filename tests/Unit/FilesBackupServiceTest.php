<?php

namespace Tests\Unit;

use App\Services\Backup\FilesBackupService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class FilesBackupServiceTest extends TestCase
{
    protected string $backupDir;

    protected string $publicRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDir = storage_path('framework/testing/files-backups-'.uniqid('', true));
        $this->publicRoot = storage_path('framework/testing/public-root-'.uniqid('', true));

        File::ensureDirectoryExists($this->publicRoot.'/products');
        File::put($this->publicRoot.'/products/sample.txt', 'hello-backup');

        Config::set('backup.files.directory', $this->backupDir);
        Config::set('backup.files.keep', 1);
        Config::set('backup.files.prefer_tar', false);
        Config::set('filesystems.disks.public.root', $this->publicRoot);
        Config::set('backup.files.roots', null);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->backupDir)) {
            File::deleteDirectory($this->backupDir);
        }

        if (is_dir($this->publicRoot)) {
            File::deleteDirectory($this->publicRoot);
        }

        parent::tearDown();
    }

    public function test_creates_files_backup_and_keeps_only_one(): void
    {
        $service = app(FilesBackupService::class);

        $service->create(manual: false);
        $service->create(manual: true);

        $files = collect(File::files($this->backupDir))
            ->map(fn (\SplFileInfo $file) => $file->getFilename())
            ->all();

        $this->assertCount(1, $files);
        $this->assertStringContainsString('-manual.', $files[0]);

        $list = $service->listBackups(1);
        $this->assertCount(1, $list);
        $this->assertSame(FilesBackupService::KIND_MANUAL, $list[0]['kind']);
    }

    public function test_artisan_files_backup_command(): void
    {
        $this->artisan('shop:files-backup', ['--manual' => true])->assertSuccessful();
        $this->assertCount(1, File::files($this->backupDir));
    }
}
