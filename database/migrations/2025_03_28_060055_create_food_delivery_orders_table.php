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
        if (!Schema::hasTable('food_delivery_orders')) {
            Schema::create('food_delivery_orders', function (Blueprint $table) {
                $table->id();
                $table->string('app_name');
                $table->string('order_number');
                $table->string('customer_name');
                $table->string('customer_phone');
                $table->string('customer_address');
                $table->string('customer_email')->nullable();
                $table->json('items');
                $table->decimal('total_amount', 10, 2);
                $table->decimal('delivery_fee', 10, 2)->default(0);
                $table->decimal('discount', 10, 2)->default(0);
                $table->decimal('tax', 10, 2)->default(0);
                $table->decimal('grand_total', 10, 2);
                $table->string('special_instructions')->nullable();
                $table->string('order_status')->enum(['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'])->default('pending');
                $table->string('order_payment_method');
                $table->string('order_payment_status')->default('pending');
                $table->string('order_payment_reference')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('food_delivery_orders')) {
            $count = DB::table('food_delivery_orders')->count();
            if ($count === 0) {
                Schema::dropIfExists('food_delivery_orders');
            }
        }
    }
};
