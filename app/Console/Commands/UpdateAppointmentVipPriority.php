<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Patient;

class UpdateAppointmentVipPriority extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:update-vip-priority {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing appointments with VIP priority information based on patient status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('Updating appointment VIP priority information...');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get all appointments that need updating (where priority fields are not set)
        $appointments = Appointment::with('patient')
            ->where(function($query) {
                $query->where('is_priority_patient', false)
                      ->orWhereNull('priority_level')
                      ->orWhereNull('vip_tier');
            })
            ->get();

        $this->info("Found {$appointments->count()} appointments to check");

        $updated = 0;
        $vipCount = 0;

        foreach ($appointments as $appointment) {
            if (!$appointment->patient) {
                $this->warn("Skipping appointment {$appointment->id} - patient not found");
                continue;
            }

            $patient = $appointment->patient;
            $wasVip = $appointment->is_priority_patient;
            
            // Update VIP priority based on current patient status
            if (!$dryRun) {
                $appointment->setPriorityFromPatient($patient);
                $appointment->save();
            } else {
                // Simulate the update for dry run
                $isVip = $patient->isVip() && $patient->hasPriorityScheduling();
                if ($isVip) {
                    $priorityLevel = match($patient->vip_tier) {
                        'diamond' => 3,
                        'platinum' => 2,
                        'gold' => 1,
                        default => 0
                    };
                    $this->line("Would update appointment {$appointment->id}: VIP={$isVip}, Priority={$priorityLevel}, Tier={$patient->vip_tier}");
                }
            }

            if ($appointment->is_priority_patient || ($dryRun && $patient->isVip() && $patient->hasPriorityScheduling())) {
                $vipCount++;
                if (!$wasVip) {
                    $this->info("Appointment {$appointment->id} ({$appointment->patient_name}) set as VIP priority");
                }
            }

            $updated++;
        }

        if ($dryRun) {
            $this->info("DRY RUN COMPLETE");
            $this->info("Would update {$updated} appointments");
            $this->info("Would mark {$vipCount} appointments as VIP priority");
        } else {
            $this->info("Updated {$updated} appointments");
            $this->info("Set {$vipCount} appointments as VIP priority");
        }

        return Command::SUCCESS;
    }
}