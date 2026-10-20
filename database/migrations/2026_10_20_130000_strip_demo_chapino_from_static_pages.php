<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Page::query()
            ->whereIn('slug', ['about', 'contact'])
            ->get()
            ->each(function (Page $page): void {
                $content = (string) $page->content;
                $updated = $content;

                $updated = str_replace(
                    ['support@chapino.ir', 'biz@chapino.ir', 'chapino.ir'],
                    ['', '', ''],
                    $updated
                );

                $updated = str_replace(
                    'ما مجموعه‌ای منتخب از کالاهای دیجیتال، لوازم خانگی، پوشاک و دوره‌های آموزشی را با قیمت رقابتی عرضه می‌کنیم.',
                    '',
                    $updated
                );

                if ($updated !== $content) {
                    $page->content = trim($updated) !== '' ? $updated : '<p></p>';
                    $page->save();
                }
            });
    }

    public function down(): void
    {
        // one-way cleanup
    }
};
