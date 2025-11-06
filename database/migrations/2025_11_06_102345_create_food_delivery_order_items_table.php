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
        Schema::create('food_delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('menu_item_id')->nullable();
            $table->string('item_name');
            $table->decimal('item_price', 10, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 10, 2);
            $table->text('special_instructions')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('order_id')->references('id')->on('food_delivery_orders')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('food_delivery_menu_items')->onDelete('set null');
            
            // Indexes
            $table->index('order_id');
            $table->index('menu_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_delivery_order_items');
    }
};
