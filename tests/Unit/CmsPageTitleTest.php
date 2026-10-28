<?php

namespace Tests\Unit;

use App\Models\Page;
use App\Support\CmsPageTitle;
use Tests\TestCase;

class CmsPageTitleTest extends TestCase
{
    public function test_uses_page_title_for_browser_tab(): void
    {
        $page = new Page([
            'title' => 'قوانین و مقررات',
            'meta_title' => 'قوانین | چاپینو',
        ]);

        $this->assertSame('قوانین و مقررات', CmsPageTitle::forPage($page));
    }

    public function test_clean_meta_title_strips_suffix_and_chapino(): void
    {
        $this->assertSame('شرایط بازگشت', CmsPageTitle::cleanMetaTitle('شرایط بازگشت | چاپینo'));
    }
}
