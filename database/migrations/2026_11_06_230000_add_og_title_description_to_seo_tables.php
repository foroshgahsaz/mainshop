<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'brands', 'posts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('og_title')->nullable()->after('meta_keywords');
                $table->text('og_description')->nullable()->after('og_title');
            });
        }
    }

    public function down(): void
    {
        foreach (['categories', 'brands', 'posts'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['og_title', 'og_description']);
            });
        }
    }
};
