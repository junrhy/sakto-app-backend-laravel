<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Patient;

class PopulatePatientArns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:populate-patient-arns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate ARN for existing patients who don\'t have one';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate ARNs for existing patients...');

        $patientsWithoutArn = Patient::whereNull('arn')->orWhere('arn', '')->get();
        
        if ($patientsWithoutArn->isEmpty()) {
            $this->info('All patients already have ARNs assigned.');
            return;
        }

        $this->info("Found {$patientsWithoutArn->count()} patients without ARNs.");

        $progressBar = $this->output->createProgressBar($patientsWithoutArn->count());
        $progressBar->start();

        foreach ($patientsWithoutArn as $patient) {
            $arn = $this->generateArn($patient->client_identifier, $patient->created_at);
            
            // Ensure uniqueness
            $counter = 1;
            $originalArn = $arn;
            while (Patient::where('arn', $arn)->exists()) {
                $arn = $originalArn . '-' . str_pad($counter, 2, '0', STR_PAD_LEFT);
                $counter++;
            }

            $patient->update(['arn' => $arn]);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();
        $this->info('Successfully populated ARNs for all patients!');
    }

    /**
     * Generate ARN based on client identifier and creation date
     */
    private function generateArn($clientIdentifier, $createdAt)
    {
        $date = $createdAt->format('Ymd');
        $prefix = strtoupper(substr($clientIdentifier, 0, 3));
        
        // Get the count of patients for this client on that day
        $count = Patient::where('client_identifier', $clientIdentifier)
            ->whereDate('created_at', $createdAt->toDateString())
            ->count();
        
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$sequence}";
    }
}
