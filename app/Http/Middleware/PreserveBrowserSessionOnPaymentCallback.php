<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * بازگشت درگاه معمولاً cross-site است (POST/redirect). با SameSite=Lax کوکی سشن
 * همراه درخواست نمی‌آید؛ لاراول سشن خالی جدید می‌سازد و Set-Cookie لاگین قبلی را می‌کُشد.
 * در این حالت از array session استفاده می‌کنیم و کوکی سشن را روی پاسخ ست نمی‌کنیم
 * تا سشن احراز هویت‌شدهٔ مرورگر برای صفحهٔ نتیجه و «بازگشت به پیش‌فاکتور» بماند.
 */
class PreserveBrowserSessionOnPaymentCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isPaymentCallback($request)) {
            return $next($request);
        }

        $cookieName = (string) config('session.cookie');
        $hadBrowserSession = $cookieName !== '' && $request->cookies->has($cookieName);

        if (! $hadBrowserSession) {
            config(['session.driver' => 'array']);
        }

        $response = $next($request);

        if (! $hadBrowserSession) {
            $this->removeSessionCookies($response, $cookieName);
        }

        return $response;
    }

    protected function isPaymentCallback(Request $request): bool
    {
        return $request->is('payment/callback', 'payment/callback/*');
    }

    protected function removeSessionCookies(Response $response, string $cookieName): void
    {
        if ($cookieName === '') {
            return;
        }

        foreach ($response->headers->getCookies() as $cookie) {
            if (! $cookie instanceof Cookie) {
                continue;
            }

            if ($cookie->getName() !== $cookieName) {
                continue;
            }

            $response->headers->removeCookie(
                $cookie->getName(),
                $cookie->getPath() ?: '/',
                $cookie->getDomain() ?: ''
            );
        }
    }
}
