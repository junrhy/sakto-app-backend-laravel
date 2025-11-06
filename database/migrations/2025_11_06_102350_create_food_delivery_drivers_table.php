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
        Schema::create('food_delivery_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('vehicle_type')->nullable();
            $table->string('license_number')->nullable();
            $table->enum('status', ['available', 'busy', 'offline'])->default('available');
            $table->string('current_location')->nullable();
            $table->string('current_coordinates')->nullable(); // lat,lng
            $table->decimal('rating', 3, 2)->default(0)->nullable();
            $table->integer('total_deliveries')->default(0);
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
        Schema::dropIfExists('food_delivery_drivers');
    }
};
