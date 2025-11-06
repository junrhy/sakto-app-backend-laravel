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
        Schema::create('parcel_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->string('delivery_reference')->unique();
            $table->enum('delivery_type', ['express', 'standard', 'economy'])->default('standard');
            
            // Sender information
            $table->string('sender_name');
            $table->string('sender_phone');
            $table->string('sender_email')->nullable();
            $table->text('sender_address');
            $table->string('sender_coordinates')->nullable(); // lat,lng
            
            // Recipient information
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->string('recipient_email')->nullable();
            $table->text('recipient_address');
            $table->string('recipient_coordinates')->nullable(); // lat,lng
            
            // Package details
            $table->text('package_description');
            $table->decimal('package_weight', 10, 2); // in kg
            $table->decimal('package_length', 8, 2)->nullable(); // in cm
            $table->decimal('package_width', 8, 2)->nullable(); // in cm
            $table->decimal('package_height', 8, 2)->nullable(); // in cm
            $table->decimal('package_value', 10, 2)->nullable(); // declared value
            
            // Pricing
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('base_rate', 10, 2)->default(0);
            $table->decimal('distance_rate', 10, 2)->default(0);
            $table->decimal('weight_rate', 10, 2)->default(0);
            $table->decimal('size_rate', 10, 2)->default(0);
            $table->decimal('delivery_type_multiplier', 5, 2)->default(1.0);
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('final_cost', 10, 2)->nullable();
            
            // Delivery schedule
            $table->date('pickup_date');
            $table->time('pickup_time');
            $table->date('estimated_delivery_date')->nullable();
            $table->time('estimated_delivery_time')->nullable();
            $table->date('actual_delivery_date')->nullable();
            $table->time('actual_delivery_time')->nullable();
            
            // Courier assignment
            $table->unsignedBigInteger('courier_id')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('courier_phone')->nullable();
            
            // Status
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
            ])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            
            // External service integration
            $table->string('external_service')->nullable(); // grab, lalamove, etc
            $table->string('external_order_id')->nullable();
            $table->text('external_tracking_url')->nullable();
            
            // Metadata
            $table->text('special_instructions')->nullable();
            $table->text('notes')->nullable();
            $table->json('pricing_breakdown')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['client_identifier', 'status']);
            $table->index('delivery_reference');
            $table->index('courier_id');
            $table->index(['client_identifier', 'pickup_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parcel_deliveries');
    }
};

