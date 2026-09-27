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
                ['term' => 'کفش'],
                ['term' => 'کیف'],
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

        $this->assertSame(['کفش', 'کیف'], $fresh->popularSearchTerms());
        $this->assertFalse($fresh->all()['show_categories']);
    }
}
