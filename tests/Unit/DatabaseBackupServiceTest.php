<?php

namespace Tests\Unit;

use App\Services\Backup\DatabaseBackupService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class DatabaseBackupServiceTest extends TestCase
{
    protected string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDir = storage_path('framework/testing/db-backups-'.uniqid('', true));
        Config::set('backup.database.directory', $this->backupDir);
        Config::set('backup.database.keep', 2);
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', database_path('database.sqlite'));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->backupDir)) {
            File::deleteDirectory($this->backupDir);
        }

        parent::tearDown();
    }

    public function test_creates_sqlite_backup_and_prunes_to_two_files(): void
    {
        $service = app(DatabaseBackupService::class);

        $service->create(manual: false);
        $service->create(manual: true);
        $service->create(manual: false);

        $files = collect(File::files($this->backupDir))
            ->map(fn (\SplFileInfo $file) => $file->getFilename())
            ->all();

        $this->assertCount(2, $files);

        $list = $service->listBackups(2);
        $this->assertCount(2, $list);
        $this->assertTrue(collect($list)->contains(fn (array $row) => $row['kind'] === DatabaseBackupService::KIND_MANUAL));
    }

    public function test_artisan_command_runs_manual_flag(): void
    {
        $this->artisan('shop:database-backup', ['--manual' => true])->assertSuccessful();
        $this->assertNotEmpty(File::files($this->backupDir));
    }
}
