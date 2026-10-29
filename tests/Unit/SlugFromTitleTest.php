<?php

namespace Tests\Unit;

use App\Support\SlugFromTitle;
use PHPUnit\Framework\TestCase;

class SlugFromTitleTest extends TestCase
{
    public function test_persian_spaces_become_hyphens(): void
    {
        $this->assertSame('سرویس-چینی', SlugFromTitle::fromProductName('سرویس چینی'));
    }

    public function test_collapses_multiple_spaces(): void
    {
        $this->assertSame('a-b', SlugFromTitle::fromProductName('a   b'));
    }

    public function test_keeps_persian_latin_and_digits(): void
    {
        $name = 'آبچکان لوکس 2طبقه FALKEN کد 7050';
        $this->assertSame('آبچکان-لوکس-2طبقه-FALKEN-کد-7050', SlugFromTitle::fromProductName($name));
    }
}
