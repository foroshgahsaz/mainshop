<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AdminAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictSalesManagerAdminAccess
{
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

        foreach (AdminAccess::salesManagerAllowedRoutePatterns() as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        abort(403);
    }
}
