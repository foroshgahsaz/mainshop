<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->boolean('stackable_with_other_coupons')->default(false)->after('is_active');
            $table->json('allowed_payment_gateways')->nullable()->after('stackable_with_other_coupons');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropColumn(['stackable_with_other_coupons', 'allowed_payment_gateways']);
        });
    }
};
