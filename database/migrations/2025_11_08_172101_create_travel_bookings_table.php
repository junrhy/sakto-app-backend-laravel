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
        Schema::create('travel_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('travel_package_id')
                ->constrained('travel_packages')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->string('booking_reference')->unique();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_contact_number')->nullable();
            $table->date('travel_date');
            $table->unsignedSmallInteger('travelers_count')->default(1);
            $table->decimal('total_price', 12, 2)->default(0);
            $table->string('status')->default('pending');
            $table->string('payment_status')->default('unpaid');
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['client_identifier', 'travel_package_id']);
            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'payment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('travel_bookings');
    }
};

