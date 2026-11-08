<?php

namespace Database\Seeders;

use App\Services\Shop\MegaMenuBootstrapService;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * پر کردن مگا منوی خانواده‌محور از دسته‌ها و خانواده‌های موجود — فقط production/دستی.
 *
 * php artisan db:seed --class=MegaMenuBootstrapSeeder --force
 */
class MegaMenuBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(MegaMenuBootstrapService::class);

        if (! $service->schemaReady()) {
            throw new RuntimeException('migrate مگا منو اجرا نشده. php artisan migrate --force');
        }

        $result = $service->run(
            dryRun: false,
            includeAllRoots: true,
            forceFamilyId: null,
        );

        $out = $this->command;
        $out?->info('مگا منو از دسته‌های موجود ساخته شد.');
        $out?->table(
            ['ستون (دسته ریشه)', 'خانواده'],
            collect($result['roots'])->map(fn (array $row) => [$row['name'], $row['family']])->all(),
        );
        $out?->line(
            "خانواده در ریل مگا: {$result['families_in_mega']}"
            ." (تازه روشن‌شده: {$result['families_enabled']})"
            ." | ریشه: {$result['roots_updated']} | زیردسته: {$result['descendants_updated']}"
        );
        $out?->warn('کش: php artisan cache:clear یا پاک کردن کش از ادمین.');
    }
}
