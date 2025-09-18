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
        Schema::create('patient_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->unsignedBigInteger('encounter_id')->nullable(); // Foreign key will be added in separate migration
            
            // Diagnosis details
            $table->string('diagnosis_name');
            $table->text('diagnosis_description')->nullable();
            $table->string('icd10_code')->nullable(); // ICD-10 diagnosis code
            $table->string('snomed_code')->nullable(); // SNOMED CT code for interoperability
            
            // Diagnosis classification
            $table->enum('diagnosis_type', [
                'primary',
                'secondary',
                'differential',
                'rule_out',
                'provisional',
                'confirmed'
            ])->default('primary');
            
            $table->enum('category', [
                'acute',
                'chronic',
                'resolved',
                'recurring',
                'unknown'
            ])->default('unknown');
            
            // Clinical details
            $table->date('onset_date')->nullable();
            $table->date('diagnosis_date');
            $table->date('resolution_date')->nullable();
            $table->string('diagnosed_by')->nullable(); // Healthcare provider
            
            // Severity and status
            $table->enum('severity', [
                'mild',
                'moderate',
                'severe',
                'critical',
                'unknown'
            ])->default('unknown');
            
            $table->enum('status', [
                'active',
                'resolved',
                'in_remission',
                'recurrent',
                'inactive'
            ])->default('active');
            
            // Clinical context
            $table->text('clinical_notes')->nullable();
            $table->string('body_site')->nullable(); // Anatomical location if applicable
            $table->string('laterality')->nullable(); // left, right, bilateral
            
            // Verification and confidence
            $table->enum('verification_status', [
                'confirmed',
                'provisional',
                'differential',
                'ruled_out',
                'refuted'
            ])->default('provisional');
            
            $table->integer('confidence_level')->nullable(); // 1-100 scale
            
            // Treatment and outcome tracking
            $table->text('treatment_plan')->nullable();
            $table->text('complications')->nullable();
            $table->text('outcome_notes')->nullable();
            
            // Follow-up and monitoring
            $table->date('next_review_date')->nullable();
            $table->boolean('requires_monitoring')->default(false);
            $table->text('monitoring_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'patient_id']);
            $table->index(['client_identifier', 'diagnosis_type']);
            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'diagnosis_date']);
            $table->index(['client_identifier', 'icd10_code']);
            $table->index(['client_identifier', 'encounter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_diagnoses');
    }
};