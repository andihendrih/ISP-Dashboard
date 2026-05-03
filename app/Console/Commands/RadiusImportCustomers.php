<?php

namespace App\Console\Commands;

use App\Models\CustomerProfile;
use App\Models\Radius\Radcheck;
use App\Models\Radius\Radusergroup;
use App\Services\RadiusService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RadiusImportCustomers extends Command
{
    protected $signature = 'radius:import-customers
        {--filter= : Hanya import user yang grupnya start dengan prefix ini (mis. Home_, Broadband, Hotspot)}
        {--exclude=HS_ : Skip user yang grupnya cocok prefix ini (default HS_ legacy)}
        {--dry-run : Preview tanpa menyimpan ke database}
        {--limit=0 : Batasi jumlah row yang diimport (0 = unlimited)}';

    protected $description = 'Import user FreeRADIUS yang belum ada di customer_profiles. Mode B: skip duplicate.';

    public function handle(): int
    {
        $filter  = (string) $this->option('filter');
        $exclude = (string) $this->option('exclude');
        $dryRun  = (bool) $this->option('dry-run');
        $limit   = (int) $this->option('limit');

        $this->info("Filter: '{$filter}' | Exclude: '{$exclude}' | Dry-run: " . ($dryRun ? 'YES' : 'no'));

        $existingUsernames = CustomerProfile::query()
            ->whereNotNull('radius_username')
            ->pluck('radius_username')
            ->map(fn ($u) => strtolower($u))
            ->all();

        $existingFlip = array_flip($existingUsernames);

        $query = Radusergroup::query()
            ->select('username', 'groupname')
            ->orderBy('username');

        if ($filter !== '') {
            $query->where('groupname', 'like', $filter . '%');
        }
        if ($exclude !== '') {
            $query->where('groupname', 'not like', $exclude . '%');
        }

        $imported = 0;
        $skipped  = 0;
        $voucher  = 0;
        $rows = [];

        $query->chunk(500, function ($chunk) use (&$imported, &$skipped, &$voucher, &$rows, $existingFlip, $dryRun, $limit) {
            foreach ($chunk as $r) {
                if ($limit > 0 && $imported >= $limit) return false;

                $u = (string) $r->username;
                if (isset($existingFlip[strtolower($u)])) {
                    $skipped++;
                    continue;
                }

                $isVoucher = RadiusService::isVoucherGroup($r->groupname);
                $password  = Radcheck::where('username', $u)
                    ->where('attribute', 'Cleartext-Password')
                    ->value('value');

                $expirationStr = Radcheck::where('username', $u)
                    ->where('attribute', 'Expiration')
                    ->value('value');
                $expiredAt = null;
                if ($expirationStr) {
                    try { $expiredAt = Carbon::parse($expirationStr)->toDateString(); } catch (\Throwable) {}
                }

                $status = match (true) {
                    $isVoucher && $expiredAt && Carbon::parse($expiredAt)->isPast() => 'inactive',
                    default => 'active',
                };

                $payload = [
                    'customer_code'    => $this->nextCode(),
                    'full_name'        => $u, // placeholder — admin isi belakangan
                    'radius_username'  => $u,
                    'radius_password'  => $password,
                    'package'          => $r->groupname,
                    'service_type'     => $isVoucher ? 'hotspot' : 'pppoe',
                    'status'           => $status,
                    'billing_enabled'  => !$isVoucher,
                    'joined_at'        => now()->toDateString(),
                    'expired_at'       => $expiredAt,
                    'notes'            => 'Imported from FreeRADIUS via radius:import-customers',
                ];

                if (!$dryRun) {
                    CustomerProfile::create($payload);
                }
                $existingFlip[strtolower($u)] = true;

                $imported++;
                if ($isVoucher) $voucher++;

                if (count($rows) < 10) {
                    $rows[] = [$u, $r->groupname, $isVoucher ? 'voucher' : 'pppoe', $expiredAt ?? '—', $status];
                }
            }
        });

        $this->newLine();
        if (!empty($rows)) {
            $this->table(['username', 'group', 'type', 'expired', 'status'], $rows);
            $this->line('  ... (showing first 10 only)');
        }
        $this->newLine();
        $this->info("Imported: {$imported}  |  Skipped (already exists): {$skipped}  |  Voucher: {$voucher}");

        if ($dryRun) {
            $this->warn('Dry-run mode — tidak ada data yang disimpan. Jalankan tanpa --dry-run untuk eksekusi.');
        }
        return self::SUCCESS;
    }

    private function nextCode(): string
    {
        $prefix = 'AHNET-';
        static $next = null;
        if ($next === null) {
            $last = CustomerProfile::query()
                ->where('customer_code', 'like', $prefix.'%')
                ->orderByDesc('id')
                ->value('customer_code');
            $next = ($last && preg_match('/(\d+)$/', $last, $m)) ? ((int) $m[1]) + 1 : 1;
        }
        do {
            $candidate = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (CustomerProfile::where('customer_code', $candidate)->exists());
        return $candidate;
    }
}
