<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Refresh the top rated movies cache once a week (every Monday at 1 AM)
        $schedule->command('movies:cache-top-rated')
                 ->weekly()
                 ->mondays()
                 ->at('01:00')
                 ->appendOutputTo(storage_path('logs/movies-cache.log'));
        
        // Generate sitemap once a week (every Sunday at 3 AM)
        $schedule->command('sitemap:generate')
                 ->weekly()
                 ->sundays()
                 ->at('03:00')
                 ->appendOutputTo(storage_path('logs/sitemap-generator.log'));
		
		// Generate sitemap once a week (every Sunday at 3 AM)
        $schedule->command('sitemap:generate-from-file')
                 ->weekly()
                 ->sundays()
                 ->at('03:00')
                 ->appendOutputTo(storage_path('logs/sitemap-generator.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}