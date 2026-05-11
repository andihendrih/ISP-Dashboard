<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BillingGenerateMonthly extends Command
{
    protected $signature = 'billing:generate-monthly
        {--month= : Period in YYYY-MM (default: current month)}
        {--due= : Override due day-of-month (default: config ahnet.billing.due_day)}';

    protected $description = 'Generate monthly invoices for all active billing-enabled customers (with prorate for new customers).';

    public function handle(BillingService $billing): int
    {
        $monthArg = $this->option('month');
        if ($monthArg) {
            try {
                $period = Carbon::createFromFormat('Y-m', $monthArg)->startOfMonth();
            } catch (\Throwable $e) {
                $this->error("Format --month invalid: {$monthArg}. Pakai YYYY-MM.");
                return self::FAILURE;
            }
        } else {
            $period = Carbon::now()->startOfMonth();
        }

        $dueDay = $this->option('due') !== null ? (int) $this->option('due') : null;

        $this->info("Generating invoices untuk {$period->format('Y-m')} (due day: ".($dueDay ?? config('ahnet.billing.due_day')).')');

        $result = $billing->generateMonthlyInvoices($period->year, $period->month, $dueDay);

        $this->table(
            ['created', 'skipped', 'errors'],
            [[$result['created'], $result['skipped'], $result['errors']]]
        );

        return self::SUCCESS;
    }
}
