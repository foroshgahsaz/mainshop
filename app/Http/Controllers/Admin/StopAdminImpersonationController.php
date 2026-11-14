<?php

namespace App\Http\Controllers\Admin;

use App\Services\Auth\AdminImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StopAdminImpersonationController
{
    public function __invoke(Request $request, AdminImpersonationService $impersonation): RedirectResponse
    {
        abort_unless($request->user() !== null, 403);
        abort_unless($impersonation->isActive(), 403);

        return redirect()->to($impersonation->stop());
    }
}
