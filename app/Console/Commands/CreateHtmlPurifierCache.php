<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateHtmlPurifierCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'htmlpurifier:create-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create HTMLPurifier cache directory with proper permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cacheDir = storage_path('app/htmlpurifier');
        
        if (!File::exists($cacheDir)) {
            File::makeDirectory($cacheDir, 0755, true);
            $this->info("HTMLPurifier cache directory created at: {$cacheDir}");
        } else {
            $this->info("HTMLPurifier cache directory already exists at: {$cacheDir}");
        }
        
        // Ensure proper permissions
        chmod($cacheDir, 0755);
        $this->info("Permissions set for HTMLPurifier cache directory");
        
        return 0;
    }
} 