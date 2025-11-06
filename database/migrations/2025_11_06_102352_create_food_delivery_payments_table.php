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
        Schema::create('food_delivery_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->enum('payment_method', ['online', 'cash_on_delivery']);
            $table->decimal('amount', 10, 2);
            $table->string('payment_reference')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->json('payment_data')->nullable(); // Additional payment gateway data
            $table->timestamps();
            
            // Foreign key
            $table->foreign('order_id')->references('id')->on('food_delivery_orders')->onDelete('cascade');
            
            // Indexes
            $table->index('order_id');
            $table->index('payment_status');
            $table->index('payment_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_delivery_payments');
    }
};
