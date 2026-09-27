<?php

namespace Tests\Unit;

use App\Services\Sms\SmsTemplateRenderer;
use Tests\TestCase;

class SmsTemplateRendererTest extends TestCase
{
    public function test_it_replaces_placeholders(): void
    {
        $renderer = new SmsTemplateRenderer;

        $message = $renderer->render('سلام {name}، سفارش {order_code}', [
            'name' => 'علی',
            'order_code' => 'ORD-1',
        ]);

        $this->assertSame('سلام علی، سفارش ORD-1', $message);
    }
}
