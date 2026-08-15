<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {

            $table->id();

            // رابطه با کاربر
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // نام گیرنده (ممکنه با صاحب حساب فرق داشته باشه)
            $table->string('receiver_name');

            // موبایل گیرنده
            $table->string('receiver_phone', 15);

            // استان و شهر
            $table->string('province');
            $table->string('city');

            // آدرس دقیق
            $table->text('address');

            // کدپستی
            $table->string('postal_code', 10)->nullable();

            // پیش‌فرض بودن آدرس (آدرس اصلی کاربر)
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};
