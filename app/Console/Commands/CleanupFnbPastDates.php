<?php

namespace App\Console\Commands;

use App\Models\FnbOpenedDate;
use App\Models\FnbBlockedDate;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CleanupFnbPastDates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-fnb-past-dates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up past F&B opened dates and blocked dates from the database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of past dates...');
        
        $today = Carbon::today();
        
        // Clean up past opened dates
        $deletedOpenedDates = FnbOpenedDate::where('opened_date', '<', $today)->delete();
        $this->info("Deleted {$deletedOpenedDates} past opened date(s)");
        
        // Clean up past blocked dates
        $deletedBlockedDates = FnbBlockedDate::where('blocked_date', '<', $today)->delete();
        $this->info("Deleted {$deletedBlockedDates} past blocked date(s)");
        
        $this->info('Cleanup completed successfully!');
        
        return Command::SUCCESS;
    }
}
