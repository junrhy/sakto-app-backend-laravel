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
        Schema::create('transportation_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier');
            $table->unsignedBigInteger('truck_id');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('customer_company')->nullable();
            $table->text('pickup_location');
            $table->text('delivery_location');
            $table->date('pickup_date');
            $table->time('pickup_time');
            $table->date('delivery_date');
            $table->time('delivery_time');
            $table->text('cargo_description');
            $table->decimal('cargo_weight', 10, 2);
            $table->string('cargo_unit')->default('kg'); // kg, tons, pieces, etc.
            $table->text('special_requirements')->nullable();
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->string('status')->default('Pending'); // Pending, Confirmed, In Progress, Completed, Cancelled
            $table->text('notes')->nullable();
            $table->string('booking_reference')->unique();
            $table->timestamps();

            $table->foreign('truck_id')->references('id')->on('transportation_fleets')->onDelete('cascade');
            $table->index(['client_identifier', 'status']);
            $table->index('booking_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transportation_bookings');
    }
};
