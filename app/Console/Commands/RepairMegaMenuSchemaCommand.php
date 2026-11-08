<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class RepairMegaMenuSchemaCommand extends Command
{
    protected $signature = 'shop:repair-mega-menu-schema';

    protected $description = 'اجرای migration تعمیر اسکیما مگا منو (جدول mega_menu_links و ستون‌ها)';

    public function handle(): int
    {
        Artisan::call('migrate', [
            '--force' => true,
            '--path' => 'database/migrations/2026_11_08_130000_repair_mega_menu_schema.php',
        ]);
        $this->line(Artisan::output());

        Artisan::call('migrate', [
            '--force' => true,
            '--path' => 'database/migrations/2026_11_08_160000_ensure_mega_menu_links_table.php',
        ]);
        $this->line(Artisan::output());

        Artisan::call('migrate', [
            '--force' => true,
            '--path' => 'database/migrations/2026_11_08_140000_mega_menu_root_category.php',
        ]);
        $this->line(Artisan::output());

        $this->info('تعمیر اسکیما مگا منو تمام شد.');

        return self::SUCCESS;
    }
}
