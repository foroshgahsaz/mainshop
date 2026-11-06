<?php

namespace Tests\Unit;

use App\Support\AdminContentSeoAnalyzer;
use PHPUnit\Framework\TestCase;

class AdminContentSeoAnalyzerTest extends TestCase
{
    public function test_requires_focus_keyword(): void
    {
        $checks = AdminContentSeoAnalyzer::analyze([
            'focus_keyword' => '',
            'primary_title' => 'عنوان',
            'meta_title' => 'عنوان تست',
            'meta_description' => str_repeat('a', 130),
            'content_html' => '<p>متن</p>',
        ]);

        $this->assertSame('focus_keyword', $checks[0]['id']);
        $this->assertSame('bad', $checks[0]['status']);
    }

    public function test_detects_focus_in_meta_and_intro(): void
    {
        $checks = AdminContentSeoAnalyzer::analyze([
            'focus_keyword' => 'خرید سرویس چینی',
            'primary_title' => 'محصول',
            'meta_title' => 'خرید سرویس چینی با بهترین قیمت',
            'meta_description' => 'در این صفحه خرید سرویس چینی را توضیح می‌دهیم و به نکات مهم اشاره می‌کنیم تا بهترین انتخاب را داشته باشید.',
            'content_html' => '<p>خرید سرویس چینی یکی از تصمیم‌های مهم آشپزخانه است.</p><p> پاراگراف دوم بدون عبارت اصلی می‌تواند ادامه دهد.</p><h2>خرید سرویس چینی و مقایسه</h2>',
            'short_text' => '',
        ]);

        $byId = [];
        foreach ($checks as $c) {
            $byId[$c['id']] = $c;
        }
        $this->assertSame('good', $byId['focus_in_meta_title']['status']);
        $this->assertSame('good', $byId['focus_in_intro']['status']);
        $this->assertSame('good', $byId['subheadings']['status']);
    }

    public function test_paragraph_without_keyword_flagged_when_multiple_paragraphs(): void
    {
        $checks = AdminContentSeoAnalyzer::analyze([
            'focus_keyword' => 'کلید تست',
            'primary_title' => 'ت',
            'meta_title' => 'کلید تست در عنوان',
            'meta_description' => str_repeat('جمله ', 40).'کلید تست',
            'content_html' => '<p>این پاراگراف اول کلید تست را دارد.</p><p>دومین پاراگراف کاملاً بی‌ربط و بدون کلمه است و فقط متن طولانی برای تست اضافه می‌کنیم تا شرط طول پاراگراف رد شود.</p><p>سومین پاراگراف هم کلید تست را دارد برای توازن.</p>',
        ]);

        $byId = [];
        foreach ($checks as $c) {
            $byId[$c['id']] = $c;
        }
        $this->assertTrue(in_array($byId['paragraphs_missing_keyword']['status'], ['ok', 'bad'], true));
    }
}
