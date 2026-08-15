<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {

            $table->id();

            // برای ساخت دسته‌های چند سطحی (دسته مادر)
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            // نام دسته
            $table->string('name');

            // اسلاگ برای URL
            $table->string('slug')->unique();

            // توضیحات دسته (اختیاری)
            $table->text('description')->nullable();

            // تصویر دسته (مثلاً بنر یا آیکون)
            $table->string('image')->nullable();

            // وضعیت فعال/غیرفعال
            $table->boolean('is_active')->default(true);

            // ترتیب نمایش
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
