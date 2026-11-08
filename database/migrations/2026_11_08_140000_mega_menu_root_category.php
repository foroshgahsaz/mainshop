<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mega_menu_links')) {
            return;
        }

        if (! Schema::hasColumn('mega_menu_links', 'root_category_id')) {
            Schema::table('mega_menu_links', function (Blueprint $table) {
                $table->unsignedBigInteger('root_category_id')->nullable()->after('id');
                $table->index('root_category_id');
            });
        }

    }

    public function down(): void
    {
        if (! Schema::hasTable('mega_menu_links')) {
            return;
        }

        if (Schema::hasColumn('mega_menu_links', 'root_category_id')) {
            Schema::table('mega_menu_links', function (Blueprint $table) {
                $table->dropIndex(['root_category_id']);
                $table->dropColumn('root_category_id');
            });
        }
    }
};
