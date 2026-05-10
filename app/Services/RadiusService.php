<?php

namespace App\Services;

use App\Models\HotspotVoucher;
use App\Models\Radius\Radacct;
use App\Models\Radius\Radcheck;
use App\Models\Radius\Radgroupreply;
use App\Models\Radius\Radreply;
use App\Models\Radius\Radusergroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * RadiusService — encapsulates all writes/reads against FreeRADIUS tables.
 *
 * Conventions:
 *  - PPPoE / Hotspot users authenticate via Cleartext-Password in radcheck.
 *  - Group membership is set in radusergroup; per-user reply attributes go
 *    in radreply (e.g. Mikrotik-Rate-Limit). Group-wide policies belong in
 *    radgroupreply.
 *  - Simultaneous-Use is set per user in radcheck.
 */
class RadiusService
{
    /**
     * Create or replace a PPPoE user in FreeRADIUS.
     *
     * @return array{username: string, password: string}
     */
    public function createPppoeUser(
        string $username,
        string $password,
        ?string $group = null,
        ?string $rateLimit = null,
        int $simultaneousUse = 1
    ): array {
        $group ??= config('ahnet.radius.default_pppoe_group');

        DB::connection('radius')->transaction(function () use ($username, $password, $group, $rateLimit, $simultaneousUse) {
            // Cleartext password
            Radcheck::updateOrCreate(
                ['username' => $username, 'attribute' => 'Cleartext-Password'],
                ['op' => ':=', 'value' => $password]
            );

            // Simultaneous-Use
            Radcheck::updateOrCreate(
                ['username' => $username, 'attribute' => 'Simultaneous-Use'],
                ['op' => ':=', 'value' => (string) $simultaneousUse]
            );

            // Group membership (clear previous, then assign)
            Radusergroup::where('username', $username)->delete();
            Radusergroup::create([
                'username'  => $username,
                'groupname' => $group,
                'priority'  => 1,
            ]);

            // Service-Type so framework treats this as Framed-User (PPPoE)
            Radreply::updateOrCreate(
                ['username' => $username, 'attribute' => 'Service-Type'],
                ['op' => ':=', 'value' => 'Framed-User']
            );

            // Per-user rate limit (overrides group)
            if ($rateLimit !== null && $rateLimit !== '') {
                Radreply::updateOrCreate(
                    ['username' => $username, 'attribute' => 'Mikrotik-Rate-Limit'],
                    ['op' => ':=', 'value' => $rateLimit]
                );
            } else {
                Radreply::where('username', $username)
                    ->where('attribute', 'Mikrotik-Rate-Limit')
                    ->delete();
            }
        });

        return ['username' => $username, 'password' => $password];
    }

    /**
     * Create a hotspot voucher (username == password). Persists to both
     * FreeRADIUS and the hotspot_vouchers table for tracking.
     */
    public function createHotspotVoucher(
        ?string $code = null,
        ?string $group = null,
        ?string $rateLimit = null,
        ?int $validMinutes = null,
        ?int $createdBy = null,
        ?string $batchId = null
    ): HotspotVoucher {
        $code ??= $this->generateVoucherCode();
        $group ??= config('ahnet.radius.default_hotspot_group');

        DB::connection('radius')->transaction(function () use ($code, $group, $rateLimit, $validMinutes) {
            Radcheck::updateOrCreate(
                ['username' => $code, 'attribute' => 'Cleartext-Password'],
                ['op' => ':=', 'value' => $code]
            );
            Radusergroup::where('username', $code)->delete();
            Radusergroup::create(['username' => $code, 'groupname' => $group, 'priority' => 1]);

            if ($rateLimit) {
                Radreply::updateOrCreate(
                    ['username' => $code, 'attribute' => 'Mikrotik-Rate-Limit'],
                    ['op' => ':=', 'value' => $rateLimit]
                );
            }
            if ($validMinutes !== null && $validMinutes > 0) {
                Radcheck::updateOrCreate(
                    ['username' => $code, 'attribute' => 'Session-Timeout'],
                    ['op' => ':=', 'value' => (string) ($validMinutes * 60)]
                );
            }
        });

        return HotspotVoucher::create([
            'code'          => $code,
            'batch_id'      => $batchId,
            'profile'       => $group,
            'rate_limit'    => $rateLimit,
            'valid_minutes' => $validMinutes,
            'status'        => 'unused',
            'created_by'    => $createdBy,
        ]);
    }

    /**
     * Hard delete a RADIUS user and ALL related rows (radcheck, radreply,
     * radusergroup, radacct, radpostauth). Use when CRM permanently removes a
     * customer to avoid leftover state in FreeRADIUS.
     */
    public function deleteUser(string $username): void
    {
        DB::connection('radius')->transaction(function () use ($username) {
            Radcheck::where('username', $username)->delete();
            Radreply::where('username', $username)->delete();
            Radusergroup::where('username', $username)->delete();
            // Clean accounting + auth log too — prevents data buildup
            DB::connection('radius')->table('radacct')->where('username', $username)->delete();
            DB::connection('radius')->table('radpostauth')->where('username', $username)->delete();
        });
    }

    /* ----------------------------------------------------------------- */
    /* Group discovery (dynamic from FreeRADIUS)                         */
    /* ----------------------------------------------------------------- */

    /**
     * Return all configured groups in `radgroupreply`, optionally excluding
     * legacy/test groups (e.g. HS_*). Result is cached briefly per request.
     *
     * @return Collection<int, string>
     */
    public function listGroups(bool $excludeLegacy = true): Collection
    {
        $rows = Radgroupreply::query()
            ->select('groupname')
            ->distinct()
            ->orderBy('groupname')
            ->pluck('groupname');

        if ($excludeLegacy) {
            $rows = $rows->reject(fn ($g) => str_starts_with((string) $g, 'HS_'));
        }
        return $rows->values();
    }

    /**
     * Categorize groups for UI dropdowns / billing rules.
     *
     * @return array{home: array, broadband: array, bisnis: array, hotspot: array, other: array}
     */
    public function categorizeGroups(?Collection $groups = null): array
    {
        $groups ??= $this->listGroups();
        $buckets = ['home' => [], 'broadband' => [], 'bisnis' => [], 'hotspot' => [], 'other' => []];

        foreach ($groups as $g) {
            $key = match (true) {
                str_starts_with((string) $g, 'Home_')      => 'home',
                str_starts_with((string) $g, 'Broadband')  => 'broadband',
                str_starts_with((string) $g, 'Bisnis')     => 'bisnis',
                str_starts_with((string) $g, 'Hotspot')    => 'hotspot',
                default                                    => 'other',
            };
            $buckets[$key][] = $g;
        }
        return $buckets;
    }

    /** True if a group is hotspot voucher (non-billable). */
    public static function isVoucherGroup(?string $groupname): bool
    {
        if ($groupname === null || $groupname === '') return false;
        return str_starts_with($groupname, 'Hotspot') || str_starts_with($groupname, 'HS_');
    }

    /** Set or clear a Mikrotik-Rate-Limit attribute for a user. */
    public function setRateLimit(string $username, ?string $rateLimit): void
    {
        if ($rateLimit === null || $rateLimit === '') {
            Radreply::where('username', $username)
                ->where('attribute', 'Mikrotik-Rate-Limit')
                ->delete();
            return;
        }

        Radreply::updateOrCreate(
            ['username' => $username, 'attribute' => 'Mikrotik-Rate-Limit'],
            ['op' => ':=', 'value' => $rateLimit]
        );
    }

    public function setGroup(string $username, string $group): void
    {
        Radusergroup::where('username', $username)->delete();
        Radusergroup::create(['username' => $username, 'groupname' => $group, 'priority' => 1]);
    }

    public function setPassword(string $username, string $password): void
    {
        Radcheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Cleartext-Password'],
            ['op' => ':=', 'value' => $password]
        );
    }

    public function setSimultaneousUse(string $username, int $count): void
    {
        Radcheck::updateOrCreate(
            ['username' => $username, 'attribute' => 'Simultaneous-Use'],
            ['op' => ':=', 'value' => (string) $count]
        );
    }

    /** Apply a group-wide rate limit (radgroupreply). */
    public function setGroupRateLimit(string $groupname, ?string $rateLimit): void
    {
        if ($rateLimit === null || $rateLimit === '') {
            Radgroupreply::where('groupname', $groupname)
                ->where('attribute', 'Mikrotik-Rate-Limit')
                ->delete();
            return;
        }

        Radgroupreply::updateOrCreate(
            ['groupname' => $groupname, 'attribute' => 'Mikrotik-Rate-Limit'],
            ['op' => ':=', 'value' => $rateLimit]
        );
    }

    /* ----------------------------------------------------------------- */
    /* Reads                                                             */
    /* ----------------------------------------------------------------- */

    /** Active radacct sessions (acctstoptime IS NULL). */
    public function activeSessions()
    {
        return Radacct::active()->orderByDesc('acctstarttime');
    }

    public function isOnline(string $username): bool
    {
        return Radacct::active()->where('username', $username)->exists();
    }

    public function lastSession(string $username): ?Radacct
    {
        return Radacct::where('username', $username)
            ->orderByDesc('acctstarttime')
            ->first();
    }

    public function userGroup(string $username): ?string
    {
        return Radusergroup::where('username', $username)->value('groupname');
    }

    /* ----------------------------------------------------------------- */
    /* Generators                                                        */
    /* ----------------------------------------------------------------- */

    /**
     * Alphanumeric-only PPPoE username. Special chars dihindari karena
     * banyak Mikrotik/ONU GUI/CLI gagal resolve PPPoE auth kalau username
     * mengandung `$`, `#`, `&`, `*`, dll (shell quoting + chr restriction).
     */
    public function generatePppoeUsername(string $name, ?string $prefix = null): string
    {
        $prefix ??= config('ahnet.radius.pppoe_username_prefix');
        $slug = Str::slug(Str::lower($name), '');
        $slug = $slug !== '' ? Str::limit($slug, 16, '') : 'user';
        // suffix angka 3 digit (cukup untuk uniqueness saat slug duplikat)
        $suffix = (string) random_int(100, 999);
        return $prefix . $slug . $suffix;
    }

    /**
     * Alphanumeric-only PPPoE password. Exclude ambiguous chars (0/O/1/l/I)
     * supaya pelanggan gampang baca + ketik. Min 1 huruf besar, 1 huruf
     * kecil, 1 angka. Aman lewat PAP/CHAP/MS-CHAP & semua GUI/CLI vendor.
     */
    public function generatePppoePassword(int $length = 10): string
    {
        $lower  = 'abcdefghjkmnpqrstuvwxyz';   // exclude i,l,o
        $upper  = 'ABCDEFGHJKLMNPQRSTUVWXYZ';  // exclude I,O
        $digits = '23456789';                   // exclude 0,1
        $pool   = $lower . $upper . $digits;

        $length = max($length, 6);
        $out = '';
        for ($i = 0; $i < $length - 3; $i++) {
            $out .= $pool[random_int(0, strlen($pool) - 1)];
        }
        // ensure at least 1 lowercase + 1 uppercase + 1 digit
        $out .= $lower[random_int(0, strlen($lower) - 1)];
        $out .= $upper[random_int(0, strlen($upper) - 1)];
        $out .= $digits[random_int(0, strlen($digits) - 1)];
        return str_shuffle($out);
    }

    /** Lowercase letters only, default 5 chars (per spec). */
    public function generateVoucherCode(int $length = 5): string
    {
        $alpha = 'abcdefghjkmnpqrstuvwxyz';   // omit ambiguous i,l,o
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alpha[random_int(0, strlen($alpha) - 1)];
        }
        // ensure uniqueness
        if (Radcheck::where('username', $code)->exists()) {
            return $this->generateVoucherCode($length);
        }
        return $code;
    }

    /* ----------------------------------------------------------------- */
    /* Stats                                                             */
    /* ----------------------------------------------------------------- */

    /** Counts grouped by status. */
    public function userStats(): array
    {
        $totalUsers = Radcheck::where('attribute', 'Cleartext-Password')->count();
        $online     = Radacct::active()->distinct('username')->count('username');

        return [
            'total_users' => $totalUsers,
            'online'      => $online,
            'offline'     => max($totalUsers - $online, 0),
        ];
    }

    /** Number of unique sessions per month for a year. */
    public function newUsersPerMonth(int $year): array
    {
        $driver = DB::connection('radius')->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "CAST(strftime('%m', acctstarttime) AS INTEGER)"
            : 'MONTH(acctstarttime)';

        $rows = Radacct::query()
            ->whereYear('acctstarttime', $year)
            ->selectRaw("{$monthExpr} as m, COUNT(DISTINCT username) as c")
            ->groupBy('m')
            ->pluck('c', 'm')
            ->all();
        return array_map(fn($m) => (int) ($rows[$m] ?? 0), range(1, 12));
    }

    public function recentSessions(int $limit = 10)
    {
        return Radacct::orderByDesc('acctstarttime')->limit($limit)->get();
    }

    public function expiredUsers(Carbon $cutoff)
    {
        // Heuristic: users whose latest session ended before cutoff and have no
        // active session right now.
        return Radacct::query()
            ->whereNotNull('acctstoptime')
            ->where('acctstoptime', '<', $cutoff)
            ->whereNotIn('username', Radacct::active()->pluck('username'));
    }
}
