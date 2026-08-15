<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attributes', function (Blueprint $table) {

            $table->id();

            // نام ویژگی (مثال: رنگ، سایز، حافظه)
            $table->string('name');

            // اسلاگ یکتا برای استفاده در سیستم
            $table->string('slug')->unique();

            // نوع نمایش ویژگی
            // text = متن ساده
            // color = انتخاب رنگ
            // select = انتخاب از لیست
            $table->enum('type', ['text', 'color', 'select'])->default('select');

            // ترتیب نمایش در پنل یا سایت
            $table->unsignedInteger('position')->default(0);

            // فعال یا غیرفعال بودن ویژگی
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attributes');
    }
};
