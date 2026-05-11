<?php

namespace App\Console\Commands;

use App\Services\TenantBillingService;
use Illuminate\Console\Command;

class TenantBillingGenerateMonthly extends Command
{
    protected $signature = 'tenant-billing:generate-monthly';
    protected $description = 'Generate invoice bulanan untuk subscription tenant yang due';

    public function handle(TenantBillingService $svc): int
    {
        $n = $svc->generateMonthlyInvoices();
        $this->info("Generated {$n} tenant invoice(s).");
        return self::SUCCESS;
    }
}
