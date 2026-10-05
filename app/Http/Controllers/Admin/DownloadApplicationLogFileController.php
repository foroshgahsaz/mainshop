<?php

namespace App\Http\Controllers\Admin;

use App\Services\Logging\ApplicationLogReader;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadApplicationLogFileController
{
    public function __invoke(Request $request, ApplicationLogReader $reader): BinaryFileResponse
    {
        abort_unless(AdminAccess::canManageShopInAdmin(), 403);

        $basename = basename((string) $request->query('file', ''));
        $path = $reader->resolvePathByBasename($basename);

        abort_unless($path !== null, 404);

        return response()->download($path, $basename, [
            'Content-Type' => $reader->mimeTypeForBasename($basename),
        ]);
    }
}
