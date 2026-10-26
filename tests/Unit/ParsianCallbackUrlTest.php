<?php

namespace Tests\Unit;

use App\Services\Payment\ParsianCallbackUrl;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ParsianCallbackUrlTest extends TestCase
{
    public function test_absolute_callback_strips_query_and_trailing_slash(): void
    {
        Config::set('app.url', 'https://www.example.ir');

        $url = ParsianCallbackUrl::absolute([
            'callback_url' => 'payment/callback/parsian/?payment=ignored',
        ]);

        $this->assertSame('https://www.example.ir/payment/callback/parsian', $url);
    }
}
