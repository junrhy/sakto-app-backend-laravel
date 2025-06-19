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
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->string('client_identifier');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('billing_address')->nullable();
            $table->json('order_items');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('order_status', [
                'pending', 
                'confirmed', 
                'processing', 
                'shipped', 
                'delivered', 
                'cancelled', 
                'refunded'
            ])->default('pending');
            $table->enum('payment_status', [
                'pending', 
                'paid', 
                'failed', 
                'refunded', 
                'partially_refunded'
            ])->default('pending');
            $table->enum('payment_method', [
                'cash', 
                'card', 
                'bank_transfer', 
                'digital_wallet', 
                'cod'
            ])->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['client_identifier', 'order_status']);
            $table->index(['client_identifier', 'payment_status']);
            $table->index('order_number');
            $table->index('customer_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_orders');
    }
};
