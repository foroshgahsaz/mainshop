<?php

namespace App\Http\Middleware;

use App\Models\ShopUrlRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleShopUrlRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $prefix = ltrim($request->path(), '/');
        if (
            $prefix === 'admin'
            || str_starts_with($prefix, 'admin/')
            || str_starts_with($prefix, 'livewire/')
            || str_starts_with($prefix, 'payment/callback')
            || str_starts_with($prefix, 'thumb/')
            || str_starts_with($prefix, 'data/')
        ) {
            return $next($request);
        }

        $path = '/'.ltrim($request->path(), '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/') ?: '/';
        }

        $map = ShopUrlRedirect::activeMapByPath();
        $redirect = $map->get($path);

        if ($redirect instanceof ShopUrlRedirect) {
            return $redirect->resolveRedirectResponse();
        }

        return $next($request);
    }
}
