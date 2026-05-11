<?php

namespace App\Console\Commands;

use App\Services\TenantBillingService;
use Illuminate\Console\Command;

class TenantBillingMarkOverdue extends Command
{
    protected $signature = 'tenant-billing:mark-overdue';
    protected $description = 'Tandai invoice tenant overdue setelah due_date lewat';

    public function handle(TenantBillingService $svc): int
    {
        $n = $svc->markOverdue();
        $this->info("Marked {$n} tenant invoice(s) overdue.");
        return self::SUCCESS;
    }
}
