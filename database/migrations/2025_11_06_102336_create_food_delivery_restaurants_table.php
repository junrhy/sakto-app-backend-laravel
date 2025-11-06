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
        Schema::create('food_delivery_restaurants', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover_image')->nullable();
            $table->text('address');
            $table->string('coordinates')->nullable(); // lat,lng
            $table->string('phone');
            $table->string('email')->nullable();
            $table->json('operating_hours')->nullable(); // {day: {open: "09:00", close: "22:00"}}
            $table->json('delivery_zones')->nullable(); // [{name: "Zone 1", coordinates: [...]}]
            $table->enum('status', ['active', 'inactive', 'closed'])->default('active');
            $table->decimal('rating', 3, 2)->default(0)->nullable(); // 0.00 to 5.00
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('minimum_order_amount', 10, 2)->default(0);
            $table->integer('estimated_prep_time')->default(30); // minutes
            $table->timestamps();
            
            // Indexes
            $table->index(['client_identifier', 'status']);
            $table->index('status');
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_delivery_restaurants');
    }
};
