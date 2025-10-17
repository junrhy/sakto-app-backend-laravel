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
        Schema::create('fnb_table_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->unsignedBigInteger('table_id');
            $table->date('schedule_date');
            $table->json('timeslots'); // Array of time slots (e.g., ["08:00", "08:30", ...])
            $table->enum('status', ['available', 'unavailable', 'joined'])->default('available');
            $table->string('joined_with')->nullable(); // Comma-separated table IDs if joined
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'schedule_date']);
            $table->index(['table_id', 'schedule_date']);
            
            // Foreign key
            $table->foreign('table_id')->references('id')->on('fnb_tables')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fnb_table_schedules');
    }
};
