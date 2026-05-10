<?php

namespace App\Console\Commands;

use App\Models\CustomerProfile;
use App\Services\CustomerAccountService;
use Illuminate\Console\Command;

/**
 * Backfill portal user accounts (role=customer) for existing customers
 * yang belum punya akun. Aman di-jalankan berkali-kali — skip yang sudah ada.
 */
class CustomersCreateAccounts extends Command
{
    protected $signature = 'customers:create-accounts {--dry-run : Tampilkan rencana tanpa menulis}';
    protected $description = 'Backfill akun portal (User role=customer) untuk semua CustomerProfile yang belum terhubung.';

    public function handle(CustomerAccountService $accounts): int
    {
        $missing = CustomerProfile::doesntHave('user')->orderBy('id')->get();

        if ($missing->isEmpty()) {
            $this->info('Semua pelanggan sudah punya akun portal.');
            return self::SUCCESS;
        }

        $this->info("Found {$missing->count()} pelanggan tanpa akun portal.");
        if ($this->option('dry-run')) {
            foreach ($missing as $c) {
                $this->line(" - {$c->customer_code} | {$c->full_name} | email={$c->email}");
            }
            $this->warn('Dry-run, tidak ada perubahan disimpan.');
            return self::SUCCESS;
        }

        $created = 0;
        foreach ($missing as $c) {
            try {
                $res = $accounts->ensureForCustomer($c);
                $pwd = $res['plain_password'] ?? '(existing)';
                $this->line(" + {$c->customer_code} | {$res['user']->email} | pwd={$pwd}");
                $created++;
            } catch (\Throwable $e) {
                $this->error(" ! {$c->customer_code}: {$e->getMessage()}");
            }
        }

        $this->info("Done. {$created} akun portal dibuat.");
        return self::SUCCESS;
    }
}
