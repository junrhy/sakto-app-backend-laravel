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
        Schema::create('patient_medications', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            
            // Medication details
            $table->string('medication_name');
            $table->string('generic_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('strength')->nullable(); // e.g., "500mg", "10mg/ml"
            $table->string('dosage_form')->nullable(); // tablet, capsule, liquid, injection, etc.
            
            // Dosing information
            $table->string('dosage')->nullable(); // e.g., "1 tablet", "5ml"
            $table->string('frequency')->nullable(); // e.g., "twice daily", "every 8 hours"
            $table->string('route')->nullable(); // oral, IV, IM, topical, etc.
            $table->text('instructions')->nullable(); // special instructions
            
            // Dates and duration
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->integer('duration_days')->nullable();
            $table->boolean('as_needed')->default(false); // PRN medication
            $table->string('indication')->nullable(); // What it's prescribed for
            
            // Prescriber information
            $table->string('prescribed_by')->nullable();
            $table->string('prescriber_license')->nullable();
            $table->date('prescription_date')->nullable();
            $table->integer('refills_remaining')->nullable();
            
            // Status and monitoring
            $table->enum('status', [
                'active',
                'discontinued',
                'completed',
                'on_hold',
                'cancelled'
            ])->default('active');
            
            $table->enum('medication_type', [
                'prescription',
                'over_the_counter',
                'supplement',
                'herbal',
                'other'
            ])->default('prescription');
            
            // Clinical codes
            $table->string('ndc_code')->nullable(); // National Drug Code
            $table->string('rxnorm_code')->nullable(); // RxNorm code for interoperability
            
            // Monitoring and notes
            $table->text('side_effects_experienced')->nullable();
            $table->text('notes')->nullable();
            $table->enum('adherence', [
                'excellent',
                'good',
                'fair',
                'poor',
                'unknown'
            ])->default('unknown');
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'patient_id']);
            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'medication_type']);
            $table->index(['client_identifier', 'start_date']);
            $table->index(['client_identifier', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medications');
    }
};