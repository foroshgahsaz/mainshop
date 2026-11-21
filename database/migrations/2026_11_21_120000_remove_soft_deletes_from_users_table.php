<?php

use App\Models\User;
use App\Services\User\UserForceDeletionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'deleted_at')) {
            return;
        }

        $force = app(UserForceDeletionService::class);

        $ids = DB::table('users')->whereNotNull('deleted_at')->orderBy('id')->pluck('id');

        foreach ($ids as $id) {
            try {
                $user = User::query()->withoutGlobalScopes()->find($id);
                if ($user !== null) {
                    $force->systemForceDelete($user);
                }
            } catch (\Throwable) {
                // اگر یک رکورد گیر کرد، migrate بقیه را ادامه بده
            }
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'deleted_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });
    }
};