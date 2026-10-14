<?php

namespace Tests\Unit;

use App\Services\Settings\SearchPopupSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPopupSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_popular_searches(): void
    {
        $service = app(SearchPopupSettingsService::class);

        $service->saveFromAdminForm([
            'placeholder' => 'جستجو کنید...',
            'popular_searches' => [
                ['term' => 'کفش', 'link' => ''],
                ['term' => 'فروش ویژه', 'link' => '/products?sort=created_at'],
            ],
            'show_categories' => false,
            'banner' => [
                'enabled' => false,
                'image' => null,
                'title' => '',
                'subtitle' => '',
                'link' => '',
                'button_text' => 'مشاهده',
            ],
        ]);

        $fresh = app(SearchPopupSettingsService::class);

        $this->assertSame(['کفش', 'فروش ویژه'], $fresh->popularSearchTerms());
        $this->assertFalse($fresh->all()['show_categories']);

        $links = $fresh->popularSearchLinks();
        $this->assertStringContainsString('search=', $links[0]['url']);
        $this->assertStringContainsString('/products', $links[1]['url']);
    }

    public function test_all_does_not_restore_default_popular_searches_after_saving_shorter_list(): void
    {
        $service = app(SearchPopupSettingsService::class);

        $service->saveFromAdminForm([
            'placeholder' => 'جستجوی من',
            'popular_searches' => [
                ['term' => 'فقط یک', 'link' => ''],
            ],
            'show_categories' => true,
            'banner' => [
                'enabled' => false,
                'image' => null,
                'title' => '',
                'subtitle' => '',
                'link' => '',
                'button_text' => 'مشاهده',
            ],
        ]);

        $fresh = app(SearchPopupSettingsService::class);

        $this->assertSame(['فقط یک'], $fresh->popularSearchTerms());
        $this->assertSame('جستجوی من', $fresh->all()['placeholder']);
    }
}
