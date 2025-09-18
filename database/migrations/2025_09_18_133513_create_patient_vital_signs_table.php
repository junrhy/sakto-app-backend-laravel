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
        Schema::create('patient_vital_signs', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->unsignedBigInteger('encounter_id')->nullable(); // Foreign key will be added in separate migration
            
            // Measurement timestamp
            $table->timestamp('measured_at');
            
            // Basic vital signs
            $table->decimal('systolic_bp', 5, 2)->nullable(); // Blood pressure systolic
            $table->decimal('diastolic_bp', 5, 2)->nullable(); // Blood pressure diastolic
            $table->string('bp_position')->nullable(); // sitting, standing, lying
            $table->string('bp_cuff_size')->nullable(); // adult, pediatric, large
            
            $table->decimal('heart_rate', 5, 2)->nullable(); // beats per minute
            $table->string('heart_rhythm')->nullable(); // regular, irregular
            
            $table->decimal('respiratory_rate', 5, 2)->nullable(); // breaths per minute
            $table->string('breathing_quality')->nullable(); // normal, labored, shallow
            
            $table->decimal('temperature', 5, 2)->nullable(); // temperature
            $table->enum('temperature_unit', ['celsius', 'fahrenheit'])->default('celsius');
            $table->string('temperature_route')->nullable(); // oral, rectal, axillary, tympanic, temporal
            
            $table->decimal('oxygen_saturation', 5, 2)->nullable(); // SpO2 percentage
            $table->boolean('on_oxygen')->default(false);
            $table->string('oxygen_flow_rate')->nullable(); // L/min if on oxygen
            
            // Physical measurements
            $table->decimal('weight', 8, 3)->nullable(); // in kg
            $table->decimal('height', 8, 3)->nullable(); // in cm
            $table->decimal('bmi', 5, 2)->nullable(); // calculated BMI
            $table->decimal('head_circumference', 8, 3)->nullable(); // for pediatric patients
            
            // Pain assessment
            $table->integer('pain_score')->nullable(); // 0-10 scale
            $table->string('pain_location')->nullable();
            $table->string('pain_quality')->nullable(); // sharp, dull, throbbing, etc.
            
            // Additional measurements
            $table->decimal('glucose_level', 8, 3)->nullable(); // mg/dL
            $table->string('glucose_test_type')->nullable(); // fasting, random, post-meal
            
            // Who measured and method
            $table->string('measured_by')->nullable(); // Healthcare provider
            $table->string('measurement_method')->nullable(); // manual, automated, etc.
            $table->text('notes')->nullable();
            
            // Flags for abnormal values
            $table->boolean('flagged_abnormal')->default(false);
            $table->text('abnormal_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'patient_id']);
            $table->index(['client_identifier', 'measured_at']);
            $table->index(['client_identifier', 'encounter_id']);
            $table->index(['client_identifier', 'flagged_abnormal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_vital_signs');
    }
};