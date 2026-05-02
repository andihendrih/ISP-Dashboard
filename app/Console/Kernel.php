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
        $schedule->command('snmp:poll')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('genieacs:sync')->everyFifteenMinutes()->withoutOverlapping();

        // Billing: generate invoices on day 1 at 00:05; mark overdue daily at 00:30
        $schedule->command('billing:generate-monthly')->monthlyOn(1, '00:05')->withoutOverlapping();
        $schedule->command('billing:mark-overdue')->dailyAt('00:30')->withoutOverlapping();
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
