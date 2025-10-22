<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add columns without unique constraint
        Schema::table('fnb_kitchen_orders', function (Blueprint $table) {
            $table->string('order_number')->nullable()->after('id');
            $table->string('customer_name')->nullable()->after('table_number');
            $table->text('customer_notes')->nullable()->after('customer_name');
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->timestamp('prepared_at')->nullable()->after('sent_at');
            $table->timestamp('ready_at')->nullable()->after('prepared_at');
            $table->timestamp('completed_at')->nullable()->after('ready_at');
        });

        // Populate existing records with order numbers
        $orders = DB::table('fnb_kitchen_orders')->whereNull('order_number')->get();
        foreach ($orders as $order) {
            DB::table('fnb_kitchen_orders')
                ->where('id', $order->id)
                ->update([
                    'order_number' => 'K' . str_pad($order->id, 4, '0', STR_PAD_LEFT),
                    'sent_at' => $order->created_at
                ]);
        }

        // Now add unique constraint
        Schema::table('fnb_kitchen_orders', function (Blueprint $table) {
            $table->string('order_number')->nullable(false)->change();
            $table->unique('order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fnb_kitchen_orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_number',
                'customer_name',
                'customer_notes',
                'sent_at',
                'prepared_at',
                'ready_at',
                'completed_at'
            ]);
        });
    }
};
