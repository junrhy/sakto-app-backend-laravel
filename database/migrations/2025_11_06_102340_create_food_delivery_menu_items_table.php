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
        Schema::create('food_delivery_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->unsignedBigInteger('restaurant_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_price', 10, 2)->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('preparation_time')->default(15); // minutes
            $table->json('dietary_info')->nullable(); // {vegetarian: true, vegan: false, gluten_free: true}
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('restaurant_id')->references('id')->on('food_delivery_restaurants')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('food_delivery_menu_categories')->onDelete('set null');
            
            // Indexes
            $table->index(['client_identifier', 'restaurant_id']);
            $table->index('category_id');
            $table->index('is_available');
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_delivery_menu_items');
    }
};
