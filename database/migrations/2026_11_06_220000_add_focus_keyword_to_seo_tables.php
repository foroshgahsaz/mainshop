<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'posts', 'pages', 'categories', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('focus_keyword')->nullable()->after('meta_keywords');
            });
        }
    }

    public function down(): void
    {
        foreach (['products', 'posts', 'pages', 'categories', 'brands'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('focus_keyword');
            });
        }
    }
};
