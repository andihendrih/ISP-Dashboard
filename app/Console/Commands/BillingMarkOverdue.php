<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class BillingMarkOverdue extends Command
{
    protected $signature = 'billing:mark-overdue';

    protected $description = 'Mark unpaid invoices past their due date as TERLAMBAT.';

    public function handle(BillingService $billing): int
    {
        $count = $billing->markOverdueInvoices();
        $this->info("Marked {$count} invoice(s) sebagai TERLAMBAT");
        return self::SUCCESS;
    }
}
