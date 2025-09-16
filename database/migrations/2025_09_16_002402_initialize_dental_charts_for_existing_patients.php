<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Patient;
use App\Models\PatientDentalChart;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all patients who don't have dental chart records
        $patients = Patient::whereDoesntHave('dentalChart')->get();
        
        foreach ($patients as $patient) {
            // Create 32 dental chart records for each patient
            for ($i = 1; $i <= 32; $i++) {
                PatientDentalChart::create([
                    'patient_id' => $patient->id,
                    'tooth_id' => $i,
                    'status' => 'healthy',
                    'notes' => null
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all dental chart records that were created by this migration
        // Note: This will remove ALL dental chart records, not just the ones created by this migration
        // If you need more granular rollback, you'd need to track which records were created
        PatientDentalChart::truncate();
    }
};