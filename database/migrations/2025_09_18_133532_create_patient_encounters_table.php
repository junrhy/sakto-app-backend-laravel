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
        Schema::create('patient_encounters', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            
            // Encounter identification
            $table->string('encounter_number')->unique(); // Unique encounter identifier
            $table->timestamp('encounter_datetime');
            $table->timestamp('end_datetime')->nullable();
            
            // Encounter type and classification
            $table->enum('encounter_type', [
                'outpatient',
                'inpatient',
                'emergency',
                'urgent_care',
                'telemedicine',
                'home_visit',
                'consultation',
                'follow_up',
                'preventive_care',
                'procedure',
                'other'
            ])->default('outpatient');
            
            $table->enum('encounter_class', [
                'ambulatory',
                'inpatient',
                'emergency',
                'home_health',
                'virtual'
            ])->default('ambulatory');
            
            // Location and provider information
            $table->string('location')->nullable(); // Clinic, hospital, department
            $table->string('room_number')->nullable();
            $table->string('attending_provider')->nullable();
            $table->string('referring_provider')->nullable();
            
            // SOAP Documentation
            // Subjective
            $table->text('chief_complaint')->nullable();
            $table->text('history_present_illness')->nullable();
            $table->text('review_of_systems')->nullable();
            
            // Objective
            $table->text('physical_examination')->nullable();
            $table->text('laboratory_results')->nullable();
            $table->text('diagnostic_results')->nullable();
            
            // Assessment
            $table->text('clinical_impression')->nullable();
            $table->text('differential_diagnosis')->nullable();
            
            // Plan
            $table->text('treatment_plan')->nullable();
            $table->text('medications_prescribed')->nullable();
            $table->text('procedures_ordered')->nullable();
            $table->text('follow_up_instructions')->nullable();
            $table->date('next_appointment_date')->nullable();
            
            // Clinical decision support
            $table->text('clinical_guidelines_followed')->nullable();
            $table->text('decision_rationale')->nullable();
            
            // Patient education and communication
            $table->text('patient_education_provided')->nullable();
            $table->text('patient_understanding_level')->nullable();
            $table->boolean('interpreter_used')->default(false);
            $table->string('interpreter_language')->nullable();
            
            // Encounter status and workflow
            $table->enum('status', [
                'scheduled',
                'arrived',
                'in_progress',
                'completed',
                'cancelled',
                'no_show'
            ])->default('scheduled');
            
            $table->enum('priority', [
                'routine',
                'urgent',
                'emergent',
                'stat'
            ])->default('routine');
            
            // Quality metrics
            $table->integer('patient_satisfaction_score')->nullable(); // 1-10 scale
            $table->text('patient_feedback')->nullable();
            $table->integer('encounter_duration_minutes')->nullable();
            
            // Billing and administrative
            $table->string('insurance_authorization')->nullable();
            $table->text('billing_notes')->nullable();
            $table->boolean('requires_follow_up')->default(false);
            
            // Documentation metadata
            $table->string('documented_by')->nullable();
            $table->timestamp('documentation_completed_at')->nullable();
            $table->boolean('documentation_complete')->default(false);
            
            // Additional notes
            $table->text('additional_notes')->nullable();
            $table->text('care_coordination_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'patient_id']);
            $table->index(['client_identifier', 'encounter_datetime']);
            $table->index(['client_identifier', 'encounter_type']);
            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'attending_provider']);
            $table->index(['client_identifier', 'next_appointment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_encounters');
    }
};