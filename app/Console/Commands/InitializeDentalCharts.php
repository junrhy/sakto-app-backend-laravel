<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Patient;
use App\Models\PatientDentalChart;

class InitializeDentalCharts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dental:initialize-charts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize dental chart records for all patients who don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Initializing dental charts for all patients...');

        $patients = Patient::with('dentalChart')->get();
        $initializedCount = 0;

        foreach ($patients as $patient) {
            if ($patient->dentalChart->isEmpty()) {
                $this->createDefaultDentalChart($patient->id);
                $initializedCount++;
                $this->line("Initialized dental chart for patient: {$patient->name} (ID: {$patient->id})");
            }
        }

        $this->info("Dental chart initialization complete! Initialized {$initializedCount} patients.");
    }

    /**
     * Create default dental chart records for a patient
     */
    private function createDefaultDentalChart($patientId)
    {
        for ($i = 1; $i <= 32; $i++) {
            PatientDentalChart::create([
                'patient_id' => $patientId,
                'tooth_id' => $i,
                'status' => 'healthy',
                'notes' => null
            ]);
        }
    }
}