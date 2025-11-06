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
        Schema::create('parcel_delivery_couriers', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('vehicle_type')->nullable(); // motorcycle, car, van, etc
            $table->enum('status', ['available', 'busy', 'offline'])->default('available');
            $table->string('current_location')->nullable();
            $table->string('current_coordinates')->nullable(); // lat,lng
            $table->text('notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['client_identifier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_delivery_couriers');
    }
};

