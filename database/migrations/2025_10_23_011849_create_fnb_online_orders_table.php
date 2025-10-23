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
        Schema::create('fnb_online_orders', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->unsignedBigInteger('online_store_id');
            $table->string('order_number')->unique();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->text('delivery_address');
            $table->json('items'); // Order items with quantities and prices
            $table->decimal('subtotal', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->string('status')->default('pending'); // pending, verified, preparing, ready, delivered, cancelled
            $table->string('verification_status')->default('pending'); // pending, verified, rejected
            $table->text('verification_notes')->nullable();
            $table->boolean('payment_negotiation_enabled')->default(false);
            $table->decimal('negotiated_amount', 10, 2)->nullable();
            $table->text('payment_notes')->nullable();
            $table->string('payment_status')->default('pending'); // pending, negotiated, paid, failed
            $table->string('payment_method')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            
            // Foreign key constraint
            $table->foreign('online_store_id')->references('id')->on('fnb_online_stores')->onDelete('cascade');
            
            // Indexes for performance
            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'verification_status']);
            $table->index(['client_identifier', 'payment_status']);
            $table->index(['online_store_id', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_online_orders');
    }
};