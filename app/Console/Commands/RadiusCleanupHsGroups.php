<?php

namespace App\Console\Commands;

use App\Models\Radius\Radcheck;
use App\Models\Radius\Radgroupcheck;
use App\Models\Radius\Radgroupreply;
use App\Models\Radius\Radreply;
use App\Models\Radius\Radusergroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RadiusCleanupHsGroups extends Command
{
    protected $signature = 'radius:cleanup-hs-groups
        {--prefix=HS_ : Prefix grup yang dianggap legacy/test}
        {--dry-run : Preview tanpa hapus}';

    protected $description = 'Hapus grup legacy (default HS_*) dan user yang masih nyangkut di sana.';

    public function handle(): int
    {
        $prefix = (string) $this->option('prefix');
        $dryRun = (bool) $this->option('dry-run');

        if ($prefix === '') {
            $this->error('Prefix tidak boleh kosong.');
            return self::FAILURE;
        }

        $groups = Radgroupreply::where('groupname', 'like', $prefix.'%')
            ->distinct()->pluck('groupname')->all();
        $groups = array_unique(array_merge(
            $groups,
            Radgroupcheck::where('groupname', 'like', $prefix.'%')->distinct()->pluck('groupname')->all(),
            Radusergroup::where('groupname', 'like', $prefix.'%')->distinct()->pluck('groupname')->all(),
        ));

        if (empty($groups)) {
            $this->info("Tidak ada grup '{$prefix}*' di RADIUS. Bersih.");
            return self::SUCCESS;
        }

        $userCount = Radusergroup::where('groupname', 'like', $prefix.'%')->count();

        $this->warn(sprintf("Akan menghapus %d grup '%s*' dan %d user yang masih nyangkut di sana:", count($groups), $prefix, $userCount));
        foreach ($groups as $g) $this->line("  - {$g}");

        if ($dryRun) {
            $this->warn('Dry-run — tidak ada yang dihapus. Jalankan tanpa --dry-run untuk eksekusi.');
            return self::SUCCESS;
        }

        if (!$this->confirm('Yakin lanjut hapus?', false)) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        DB::connection('radius')->transaction(function () use ($prefix) {
            $usernames = Radusergroup::where('groupname', 'like', $prefix.'%')
                ->pluck('username')->unique()->all();

            // Hapus user yang HANYA punya membership di grup legacy
            foreach ($usernames as $u) {
                $hasOtherGroups = Radusergroup::where('username', $u)
                    ->where('groupname', 'not like', $prefix.'%')
                    ->exists();
                if (!$hasOtherGroups) {
                    Radcheck::where('username', $u)->delete();
                    Radreply::where('username', $u)->delete();
                    DB::connection('radius')->table('radacct')->where('username', $u)->delete();
                    DB::connection('radius')->table('radpostauth')->where('username', $u)->delete();
                }
            }
            Radusergroup::where('groupname', 'like', $prefix.'%')->delete();
            Radgroupreply::where('groupname', 'like', $prefix.'%')->delete();
            Radgroupcheck::where('groupname', 'like', $prefix.'%')->delete();
        });

        $this->info('Selesai. Cleanup berhasil.');
        return self::SUCCESS;
    }
}
