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
        Schema::create('food_delivery_order_trackings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->enum('status', [
                'pending',
                'accepted',
                'preparing',
                'ready',
                'assigned',
                'out_for_delivery',
                'delivered',
                'cancelled'
            ]);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('updated_by')->nullable(); // user_id or system
            $table->timestamp('timestamp');
            $table->timestamps();
            
            // Foreign key
            $table->foreign('order_id')->references('id')->on('food_delivery_orders')->onDelete('cascade');
            
            // Indexes
            $table->index('order_id');
            $table->index('timestamp');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_delivery_order_trackings');
    }
};
