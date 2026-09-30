<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('line_discount_type', 16)->default('none')->after('total_price');
            $table->unsignedInteger('line_discount_value')->default(0)->after('line_discount_type');
        });

        Schema::create('order_invoice_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('title');
            $table->unsignedBigInteger('amount');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['order_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_invoice_lines');

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['line_discount_type', 'line_discount_value']);
        });
    }
};
