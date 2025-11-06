<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('food_delivery_orders');
        Schema::create('food_delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('order_reference')->unique();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->text('customer_address');
            $table->string('customer_coordinates')->nullable(); // lat,lng
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['online', 'cash_on_delivery'])->default('cash_on_delivery');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->enum('order_status', [
                'pending',
                'accepted',
                'preparing',
                'ready',
                'assigned',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ])->default('pending');
            $table->text('special_instructions')->nullable();
            $table->timestamp('estimated_delivery_time')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('restaurant_id')->references('id')->on('food_delivery_restaurants')->onDelete('cascade');
            // Note: driver_id foreign key will be added after drivers table is created
            
            // Indexes
            $table->index(['client_identifier', 'order_status']);
            $table->index('order_reference');
            $table->index('restaurant_id');
            $table->index('driver_id');
            $table->index('customer_id');
            $table->index('order_status');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_delivery_orders');
    }
};
