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
        Schema::create('patient_allergies', function (Blueprint $table) {
            $table->id();
            $table->string('client_identifier')->index();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            
            // Allergy details
            $table->string('allergen'); // What they're allergic to
            $table->enum('allergen_type', [
                'medication',
                'food',
                'environmental',
                'latex',
                'contrast_dye',
                'other'
            ]);
            
            // Reaction details
            $table->text('reaction_description');
            $table->enum('severity', [
                'mild',
                'moderate', 
                'severe',
                'life_threatening',
                'unknown'
            ])->default('unknown');
            
            // Reaction symptoms
            $table->json('symptoms')->nullable(); // Array of symptoms: rash, hives, swelling, difficulty_breathing, etc.
            
            // Clinical details
            $table->date('first_occurrence_date')->nullable();
            $table->date('last_occurrence_date')->nullable();
            $table->string('onset_time')->nullable(); // immediate, delayed, etc.
            
            // Status and verification
            $table->enum('status', ['active', 'inactive', 'resolved'])->default('active');
            $table->enum('verification_status', [
                'confirmed',
                'unconfirmed',
                'patient_reported',
                'family_reported'
            ])->default('patient_reported');
            
            // Additional information
            $table->text('notes')->nullable();
            $table->string('reported_by')->nullable(); // Who reported the allergy
            $table->timestamp('verified_date')->nullable();
            $table->string('verified_by')->nullable(); // Healthcare provider who verified
            
            $table->timestamps();
            
            // Indexes for performance and safety
            $table->index(['client_identifier', 'patient_id']);
            $table->index(['client_identifier', 'allergen_type']);
            $table->index(['client_identifier', 'severity']);
            $table->index(['client_identifier', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_allergies');
    }
};