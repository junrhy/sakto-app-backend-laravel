<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('transportation_shipment_trackings')) {
            Schema::create('transportation_shipment_trackings', function (Blueprint $table) {
                $table->id();
                $table->string('client_identifier');
                $table->foreignId('truck_id')->constrained('transportation_fleets')->onDelete('cascade');
                $table->string('driver');
                $table->string('destination');
                $table->string('origin');
                $table->date('departure_date');
                $table->date('arrival_date');
                $table->enum('status', ['Scheduled', 'In Transit', 'Delivered', 'Delayed'])->default('Scheduled');
                $table->string('cargo');
                $table->decimal('weight', 8, 2); // in tons
                $table->string('current_location')->nullable();
                $table->integer('estimated_delay')->nullable(); // in hours
                $table->string('customer_contact');
                $table->enum('priority', ['Low', 'Medium', 'High'])->default('Medium');
                $table->timestamps();
                
                $table->index('client_identifier');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('transportation_shipment_trackings')) {
            $count = DB::table('transportation_shipment_trackings')->count();
            if ($count === 0) {
                Schema::dropIfExists('transportation_shipment_trackings');
            }
        }
    }
};
