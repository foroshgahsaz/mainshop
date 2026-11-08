<?php

namespace App\Console\Commands;

use App\Models\ProductFamily;
use App\Services\Shop\MegaMenuBootstrapService;
use Illuminate\Console\Command;

class BootstrapMegaMenuCommand extends Command
{
    protected $signature = 'shop:bootstrap-mega-menu
        {--dry-run : فقط گزارش بدون ذخیره}
        {--all-roots : همه دسته‌های ریشهٔ فعال (نه فقط show_in_mega قبلی)}
        {--family= : اسلاگ یا شناسه خانواده — همه ستون‌ها زیر همین خانواده}
        {--list-families : فقط لیست خانواده‌های فعال (id, slug, name) و خروج}';

    protected $description = 'آماده‌سازی مگا منو از دسته‌ها و خانواده‌های موجود (بدون لینک دستی؛ معادل db:seed --class=MegaMenuBootstrapSeeder)';

    public function handle(MegaMenuBootstrapService $service): int
    {
        if ($this->option('list-families')) {
            $this->printFamiliesTable();

            return self::SUCCESS;
        }

        if (! $this->confirmUnlessForced()) {
            return self::SUCCESS;
        }

        try {
            $forceFamilyId = $this->resolveFamilyOption();

            $result = $service->run(
                dryRun: (bool) $this->option('dry-run'),
                includeAllRoots: (bool) $this->option('all-roots'),
                forceFamilyId: $forceFamilyId,
            );
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());
            $this->newLine();
            $this->components->warn('خانواده‌های موجود:');
            $this->printFamiliesTable();
            $this->newLine();
            $this->line('بدون --family اجرا کنید تا از محصولات/پیش‌فرض تخصیص داده شود.');
            $this->line('مثال: php artisan shop:bootstrap-mega-menu --all-roots --no-interaction');

            return self::FAILURE;
        } catch (\RuntimeException $e) {
            $this->components->error($e->getMessage());
            if (str_contains($e->getMessage(), 'دستهٔ ریشه')) {
                $this->line('امتحان: php artisan shop:bootstrap-mega-menu --all-roots --dry-run');
            }

            return self::FAILURE;
        }

        $mode = $this->option('dry-run') ? ' (dry-run)' : '';

        $this->components->info("مگا منو{$mode}:");
        $this->table(
            ['ستون (دسته ریشه)', 'خانواده'],
            collect($result['roots'])->map(fn (array $row) => [$row['name'], $row['family']])->all(),
        );

        $this->line("خانواده‌های فعال در مگا: {$result['families_enabled']}");
        $this->line("دسته ریشه: {$result['roots_updated']} | زیردسته: {$result['descendants_updated']}");

        if ($this->option('dry-run')) {
            $this->components->warn('dry-run — تغییری ذخیره نشد. برای اعمال: همان دستور بدون --dry-run');

            return self::SUCCESS;
        }

        $this->components->info('کش هدر پاک شد. در صورت نیاز: php artisan shop:cache-warm یا پاک کردن کش از ادمین.');
        $this->newLine();
        $this->line('لینک دستی: ادمین → محتوا → منوی سایت → افزودن مگا منو');

        return self::SUCCESS;
    }

    private function confirmUnlessForced(): bool
    {
        if ($this->option('dry-run')) {
            return true;
        }

        if ($this->option('no-interaction')) {
            return true;
        }

        return $this->confirm('دسته‌های ریشه (مگا منوی قبلی) به خانواده وصل و show_in_mega روشن شود؟', true);
    }

    private function resolveFamilyOption(): ?int
    {
        $family = $this->option('family');
        if ($family === null || $family === '') {
            return null;
        }

        if (is_numeric($family)) {
            $id = (int) $family;
            if (! ProductFamily::query()->whereKey($id)->exists()) {
                throw new \InvalidArgumentException("خانواده با شناسه {$id} پیدا نشد.");
            }

            return $id;
        }

        $model = ProductFamily::query()->where('slug', $family)->first();
        if (! $model) {
            throw new \InvalidArgumentException("خانواده با اسلاگ «{$family}» پیدا نشد.");
        }

        return $model->id;
    }

    private function printFamiliesTable(): void
    {
        $rows = ProductFamily::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->orderBy('id')
            ->get(['id', 'slug', 'name', 'show_in_mega'])
            ->map(fn (ProductFamily $f) => [
                $f->id,
                $f->slug,
                $f->name,
                $f->show_in_mega ? 'بله' : 'خیر',
            ])
            ->all();

        if ($rows === []) {
            $this->warn('هیچ خانوادهٔ فعالی در دیتابیس نیست.');

            return;
        }

        $this->table(['id', 'slug', 'name', 'show_in_mega'], $rows);
    }
}
