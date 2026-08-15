<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

            // شناسه کاربر
            $table->id();

            // نام
            $table->string('name');

            // ایمیل
            $table->string('email')->unique()->nullable();

            // شماره موبایل (برای لاگین)
            $table->string('phone', 15)->unique();

            // کد ملی
            $table->string('national_code', 10)->unique()->nullable();

            // زمان تایید موبایل
            $table->timestamp('phone_verified_at')->nullable();

            // آواتار
            $table->string('avatar')->nullable();

            // وضعیت حساب (فعال / غیرفعال)
            $table->boolean('status')->default(true);

            // رمز عبور
            $table->string('password');

            // توکن remember me
            $table->rememberToken();

            // created_at / updated_at
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
