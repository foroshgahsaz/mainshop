<?php

use App\Services\Shop\EnsureDarikCreditPlan;
use Illuminate\Database\Migrations\Migration;

/**
 * مایگریت قبلی ممکن است در prod به‌عنوان ran ثبت شده باشد بدون insert.
 * این ردیف جدید ensure را دوباره اجرا می‌کند (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        app(EnsureDarikCreditPlan::class)->ensure();
    }

    public function down(): void
    {
        // ردیف طرح داریک را عمداً حذف نمی‌کنیم.
    }
};
