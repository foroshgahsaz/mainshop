<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSalesManagerAdminAccess
{
    /** @var list<string> */
    protected const ALLOWED_ROUTE_PATTERNS = [
        'filament.admin.pages.dashboard',
        'filament.admin.resources.orders.*',
        'filament.admin.resources.payments.*',
        'filament.admin.resources.users.index',
        'filament.admin.resources.users.edit',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! AdminAccess::isSalesManagerOnly($user)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            return $next($request);
        }

        if ($routeName === 'filament.admin.resources.users.edit') {
            $recordId = $request->route('record');

            if ($recordId !== null && (int) $recordId === (int) $user->getKey()) {
                return $next($request);
            }
        }

        foreach (self::ALLOWED_ROUTE_PATTERNS as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
