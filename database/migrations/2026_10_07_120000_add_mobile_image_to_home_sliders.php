<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('home_sliders')) {
            return;
        }

        if (Schema::hasColumn('home_sliders', 'image_mobile')) {
            return;
        }

        Schema::table('home_sliders', function (Blueprint $table) {
            $table->string('image_mobile')->nullable()->after('image');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('home_sliders') || ! Schema::hasColumn('home_sliders', 'image_mobile')) {
            return;
        }

        Schema::table('home_sliders', function (Blueprint $table) {
            $table->dropColumn('image_mobile');
        });
    }
};
