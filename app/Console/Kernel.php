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

        // Notifications: kirim reminder H-3, H-0, dan overdue setiap pagi 08:00
        $schedule->command('notify:reminders')->dailyAt('08:00')->withoutOverlapping();

        // Tenant Billing (SaaS subscription) — superadmin nagih tenant
        $cycleDay = (int) config('ahnet.tenant_billing.cycle_day', 1);
        $schedule->command('tenant-billing:generate-monthly')->monthlyOn($cycleDay, '00:10')->withoutOverlapping();
        $schedule->command('tenant-billing:mark-overdue')->dailyAt('00:40')->withoutOverlapping();
        $schedule->command('tenant-billing:auto-suspend')->dailyAt('01:00')->withoutOverlapping();
        $schedule->command('tenant-billing:notify')->dailyAt('08:15')->withoutOverlapping();
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
