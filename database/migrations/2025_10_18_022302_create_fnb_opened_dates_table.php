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
        Schema::create('fnb_opened_dates', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->date('opened_date');
            $table->json('timeslots'); // Array of time slots (e.g., ["08:00", "08:30", ...])
            $table->text('reason')->nullable(); // Why this date is open (e.g., "Weekend hours", "Special event")
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'opened_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_opened_dates');
    }
};
