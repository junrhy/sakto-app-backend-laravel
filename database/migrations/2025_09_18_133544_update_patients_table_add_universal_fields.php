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
        Schema::table('patients', function (Blueprint $table) {
            // Only add truly missing fields for comprehensive medical records
            
            // Social history - important for medical decisions
            $table->enum('smoking_status', ['never', 'former', 'current', 'unknown'])->default('unknown')->after('client_identifier');
            $table->enum('alcohol_use', ['never', 'occasional', 'moderate', 'heavy', 'unknown'])->default('unknown')->after('smoking_status');
            $table->string('occupation')->nullable()->after('alcohol_use');
            
            // Language and cultural considerations
            $table->string('preferred_language')->default('English')->after('occupation');
            
            // Advance directives - important for healthcare decisions
            $table->boolean('has_advance_directive')->default(false)->after('preferred_language');
            $table->text('advance_directive_notes')->nullable()->after('has_advance_directive');
            
            // Patient status - active, inactive, deceased
            $table->enum('status', ['active', 'inactive', 'deceased'])->default('active')->after('advance_directive_notes');
            
            // Enhanced visit tracking
            $table->timestamp('last_visit_date')->nullable()->after('status');
            
            // Performance indexes
            $table->index(['client_identifier', 'status']);
            $table->index(['client_identifier', 'last_visit_date']);
            $table->index(['client_identifier', 'smoking_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'smoking_status', 'alcohol_use', 'occupation', 'preferred_language',
                'has_advance_directive', 'advance_directive_notes', 'status', 'last_visit_date'
            ]);
        });
    }
};