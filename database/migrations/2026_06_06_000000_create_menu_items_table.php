<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('label');
            $table->string('item_type')->default('link'); // link, mega_trigger, mega_promo, accordion
            $table->string('link_type')->default('route'); // route, url, category, page
            $table->string('link_value')->nullable();
            $table->string('location')->default('both'); // desktop, mobile, both
            $table->unsignedTinyInteger('mega_column')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('open_in_new_tab')->default(false);
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->boolean('show_in_mega')->default(true)->after('position');
            $table->unsignedTinyInteger('mega_column')->nullable()->after('show_in_mega');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['show_in_mega', 'mega_column']);
        });

        Schema::dropIfExists('menu_items');
    }
};
