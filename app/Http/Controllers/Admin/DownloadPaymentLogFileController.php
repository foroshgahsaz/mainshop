<?php

namespace App\Http\Controllers\Admin;

use App\Services\Payment\PaymentLogReader;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadPaymentLogFileController
{
    public function __invoke(Request $request, PaymentLogReader $reader): StreamedResponse
    {
        abort_unless(AdminAccess::canManageShopInAdmin(), 403);

        $basename = (string) $request->query('file', '');
        $path = $reader->resolvePathByBasename($basename);

        abort_unless($path !== null, 404);

        return response()->download($path, $basename, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
