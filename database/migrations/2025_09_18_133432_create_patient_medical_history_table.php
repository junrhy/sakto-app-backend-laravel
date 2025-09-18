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
        Schema::create('patient_medical_history', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            
            // Type of medical history entry
            $table->enum('type', [
                'past_illness',
                'surgery',
                'hospitalization',
                'family_history',
                'social_history',
                'immunization',
                'other'
            ]);
            
            // Condition/procedure details
            $table->string('condition_name');
            $table->text('description')->nullable();
            $table->date('date_occurred')->nullable();
            $table->string('icd10_code')->nullable(); // ICD-10 diagnosis code
            
            // Family history specific fields
            $table->string('family_relationship')->nullable(); // father, mother, sibling, etc.
            $table->integer('age_at_diagnosis')->nullable();
            
            // Surgery/procedure specific fields
            $table->string('surgeon_name')->nullable();
            $table->string('hospital_name')->nullable();
            $table->text('complications')->nullable();
            
            // Status and severity
            $table->enum('status', ['active', 'resolved', 'chronic', 'unknown'])->default('unknown');
            $table->enum('severity', ['mild', 'moderate', 'severe', 'unknown'])->default('unknown');
            
            // Additional notes
            $table->text('notes')->nullable();
            $table->string('source')->default('patient_reported'); // patient_reported, medical_record, family_member
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['client_identifier', 'patient_id']);
            $table->index(['client_identifier', 'type']);
            $table->index(['client_identifier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_medical_history');
    }
};