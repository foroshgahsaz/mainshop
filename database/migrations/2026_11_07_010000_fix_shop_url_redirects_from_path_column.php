<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shop_url_redirects')) {
            return;
        }

        // اگر migrate اولیه نصفه مانده باشد، ایندекс یکتا ممکن است نباشد یا ستون ۵۱۲ باشد.
        try {
            DB::statement('ALTER TABLE `shop_url_redirects` DROP INDEX `shop_url_redirects_from_path_unique`');
        } catch (\Throwable) {
            // ایندنس وجود نداشت
        }

        DB::statement(
            'ALTER TABLE `shop_url_redirects` MODIFY `from_path` VARCHAR(191) NOT NULL COMMENT \'مسیر قدیمی؛ با / شروع شود\''
        );

        Schema::table('shop_url_redirects', function (Blueprint $table) {
            $table->unique('from_path');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('shop_url_redirects')) {
            return;
        }

        Schema::table('shop_url_redirects', function (Blueprint $table) {
            $table->dropUnique(['from_path']);
        });

        DB::statement(
            'ALTER TABLE `shop_url_redirects` MODIFY `from_path` VARCHAR(512) NOT NULL'
        );
        // بدون UNIQUE — همان ۵۱۲ با ایندекс یکتا روی utf8mb4 روی بعضی MySQL شکست می‌خورد
    }
};
