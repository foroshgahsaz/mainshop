<?php

namespace Tests\Unit;

use App\Http\Middleware\PreserveBrowserSessionOnPaymentCallback;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class PreserveBrowserSessionOnPaymentCallbackTest extends TestCase
{
    public function test_payment_callback_without_session_cookie_uses_array_driver(): void
    {
        config(['session.driver' => 'database', 'session.cookie' => 'mainshop_session']);

        $middleware = new PreserveBrowserSessionOnPaymentCallback;
        $request = Request::create('/payment/callback/digipay', 'POST');

        $middleware->handle($request, function () {
            $this->assertSame('array', config('session.driver'));

            return new Response('ok');
        });
    }

    public function test_non_callback_keeps_configured_session_driver(): void
    {
        config(['session.driver' => 'database', 'session.cookie' => 'mainshop_session']);

        $middleware = new PreserveBrowserSessionOnPaymentCallback;
        $request = Request::create('/representative/payment/result/ABC', 'GET');

        $middleware->handle($request, function () {
            $this->assertSame('database', config('session.driver'));

            return new Response('ok');
        });
    }

    public function test_callback_with_existing_session_cookie_keeps_driver(): void
    {
        config(['session.driver' => 'database', 'session.cookie' => 'mainshop_session']);

        $middleware = new PreserveBrowserSessionOnPaymentCallback;
        $request = Request::create('/payment/callback/digipay', 'POST');
        $request->cookies->set('mainshop_session', 'existing-id');

        $middleware->handle($request, function () {
            $this->assertSame('database', config('session.driver'));

            return new Response('ok');
        });
    }
}
