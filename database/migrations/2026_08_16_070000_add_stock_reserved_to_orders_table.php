<?php

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('stock_reserved')->default(false);
        });

        $paidOrderIds = DB::table('payments')
            ->where('status', Payment::STATUS_SUCCESS)
            ->pluck('order_id');

        DB::table('orders')
            ->where('payment_method', 'cod')
            ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_RETURNED])
            ->update(['stock_reserved' => true]);

        if ($paidOrderIds->isNotEmpty()) {
            DB::table('orders')
                ->whereIn('id', $paidOrderIds)
                ->whereNotIn('status', [Order::STATUS_CANCELED, Order::STATUS_RETURNED])
                ->update(['stock_reserved' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_reserved');
        });
    }
};
