<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_files')) {
            Schema::create('media_files', function (Blueprint $table) {
                $table->id();
                $table->string('disk', 32)->default('public');
                $table->string('path', 512);
                $table->string('folder')->index();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('size')->default(0);
                $table->unsignedInteger('width')->nullable();
                $table->unsignedInteger('height')->nullable();
                $table->string('original_name')->nullable();
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique('path', 'media_files_path_unique');
            });
        } else {
            $this->ensurePathUniqueIndex();
        }

        if (! Schema::hasTable('media_usages')) {
            Schema::create('media_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('media_file_id')->constrained('media_files')->cascadeOnDelete();
                $table->string('usable_type', 120);
                $table->unsignedBigInteger('usable_id');
                $table->string('field', 80);
                $table->timestamps();

                $table->index(['usable_type', 'usable_id'], 'media_usages_usable_index');
                $table->unique(
                    ['usable_type', 'usable_id', 'field'],
                    'media_usages_usable_field_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('media_usages');
        Schema::dropIfExists('media_files');
    }

    protected function ensurePathUniqueIndex(): void
    {
        if ($this->indexExists('media_files', 'media_files_path_unique')) {
            return;
        }

        if ($this->indexExists('media_files', 'media_files_disk_path_unique')) {
            Schema::table('media_files', function (Blueprint $table) {
                $table->dropUnique('media_files_disk_path_unique');
            });
        }

        Schema::table('media_files', function (Blueprint $table) {
            $table->unique('path', 'media_files_path_unique');
        });
    }

    protected function indexExists(string $table, string $index): bool
    {
        foreach (Schema::getIndexes($table) as $definition) {
            if (($definition['name'] ?? '') === $index) {
                return true;
            }
        }

        return false;
    }
};
