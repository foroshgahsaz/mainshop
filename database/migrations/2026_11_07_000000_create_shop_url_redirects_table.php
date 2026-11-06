<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_url_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 512)->unique()->comment('مسیر قدیمی؛ با / شروع شود، مثلاً /old-url');
            $table->string('to_target', 1024)->comment('مقصد: مسیر سایت (با /) یا URL کامل');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_url_redirects');
    }
};
