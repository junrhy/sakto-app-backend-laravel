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
        Schema::create('parcel_delivery_trackings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parcel_delivery_id');
            $table->enum('status', [
                'pending',
                'confirmed',
                'scheduled',
                'out_for_pickup',
                'picked_up',
                'at_warehouse',
                'in_transit',
                'out_for_delivery',
                'delivery_attempted',
                'delivered',
                'returned',
                'returned_to_sender',
                'on_hold',
                'failed',
                'cancelled'
            ]);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->string('updated_by')->nullable(); // user identifier or system
            $table->timestamp('timestamp');
            
            $table->timestamps();
            
            // Foreign key
            $table->foreign('parcel_delivery_id')->references('id')->on('parcel_deliveries')->onDelete('cascade');
            
            // Indexes
            $table->index('parcel_delivery_id');
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_delivery_trackings');
    }
};

