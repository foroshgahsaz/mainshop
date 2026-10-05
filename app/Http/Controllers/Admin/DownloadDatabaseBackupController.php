<?php

namespace App\Http\Controllers\Admin;

use App\Services\Backup\DatabaseBackupService;
use App\Support\AdminAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DownloadDatabaseBackupController
{
    public function __invoke(Request $request, DatabaseBackupService $backups): BinaryFileResponse
    {
        abort_unless(AdminAccess::canManageShopInAdmin(), 403);

        $basename = basename((string) $request->query('file', ''));
        $path = $backups->resolvePathByBasename($basename);

        abort_unless($path !== null, 404);

        return response()->download($path, $basename, [
            'Content-Type' => $backups->mimeTypeForBasename($basename),
        ]);
    }
}
