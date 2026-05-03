<?php

namespace App\Http\Controllers;

use App\Models\Radius\Radacct;
use App\Models\Radius\Radcheck;
use App\Models\Radius\Radusergroup;
use App\Services\RadiusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(private RadiusService $radius) {}

    public function index(Request $request): View
    {
        $filterStatus = $request->query('status', 'all'); // all|active|expired
        $filterGroup  = $request->query('group');
        $search       = $request->query('q');

        $sub = Radusergroup::query()
            ->select('username', 'groupname')
            ->where(function ($w) {
                $w->where('groupname', 'like', 'Hotspot%')->orWhere('groupname', 'like', 'HS_%');
            });

        if ($filterGroup) $sub->where('groupname', $filterGroup);
        if ($search)      $sub->where('username', 'like', "%{$search}%");

        $rows = $sub->orderBy('username')->paginate(50)->withQueryString();

        $usernames = collect($rows->items())->pluck('username')->all();

        // Bulk fetch expirations + last seen
        $expirations = Radcheck::whereIn('username', $usernames)
            ->where('attribute', 'Expiration')
            ->pluck('value', 'username');

        $lastSeen = Radacct::whereIn('username', $usernames)
            ->selectRaw('username, MAX(acctstarttime) as last_at')
            ->groupBy('username')
            ->pluck('last_at', 'username');

        $now = Carbon::now();
        $enriched = collect($rows->items())->map(function ($r) use ($expirations, $lastSeen, $now) {
            $expRaw = $expirations[$r->username] ?? null;
            $exp = null;
            if ($expRaw) {
                try { $exp = Carbon::parse($expRaw); } catch (\Throwable) {}
            }
            return [
                'username'  => $r->username,
                'groupname' => $r->groupname,
                'expired'   => $exp,
                'is_expired'=> $exp ? $exp->lt($now) : false,
                'last_seen' => $lastSeen[$r->username] ?? null,
            ];
        });

        if ($filterStatus === 'active')  $enriched = $enriched->reject(fn ($v) => $v['is_expired']);
        if ($filterStatus === 'expired') $enriched = $enriched->filter(fn ($v) => $v['is_expired']);

        // Stats
        $total = Radusergroup::query()
            ->where(function ($w) { $w->where('groupname','like','Hotspot%')->orWhere('groupname','like','HS_%'); })
            ->count();

        $expiredCount = Radcheck::where('attribute', 'Expiration')
            ->where('value', '!=', '')
            ->whereIn('username', function ($q) {
                $q->select('username')->from('radusergroup')
                  ->where(function ($w) { $w->where('groupname','like','Hotspot%')->orWhere('groupname','like','HS_%'); });
            })
            ->get()
            ->filter(function ($r) {
                try { return Carbon::parse($r->value)->lt(now()); } catch (\Throwable) { return false; }
            })->count();

        $groups = Radusergroup::query()
            ->select('groupname')->distinct()
            ->where(function ($w) { $w->where('groupname','like','Hotspot%')->orWhere('groupname','like','HS_%'); })
            ->orderBy('groupname')
            ->pluck('groupname');

        return view('vouchers.index', [
            'rows'         => $rows,
            'enriched'     => $enriched,
            'total'        => $total,
            'expiredCount' => $expiredCount,
            'activeCount'  => $total - $expiredCount,
            'groups'       => $groups,
            'filterStatus' => $filterStatus,
            'filterGroup'  => $filterGroup,
            'search'       => $search,
        ]);
    }

    /** Hapus voucher (single). */
    public function destroy(string $username): RedirectResponse
    {
        $this->radius->deleteUser($username);
        return back()->with('success', "Voucher {$username} dihapus dari RADIUS.");
    }

    /** Bulk delete semua voucher yang expired. */
    public function bulkDeleteExpired(): RedirectResponse
    {
        $usernames = Radcheck::where('attribute', 'Expiration')
            ->whereIn('username', function ($q) {
                $q->select('username')->from('radusergroup')
                  ->where(function ($w) { $w->where('groupname','like','Hotspot%')->orWhere('groupname','like','HS_%'); });
            })
            ->get()
            ->filter(function ($r) {
                try { return Carbon::parse($r->value)->lt(now()); } catch (\Throwable) { return false; }
            })
            ->pluck('username')
            ->unique()
            ->values();

        $count = $usernames->count();
        if ($count === 0) {
            return back()->with('success', 'Tidak ada voucher expired untuk dihapus.');
        }

        DB::connection('radius')->transaction(function () use ($usernames) {
            foreach ($usernames as $u) {
                $this->radius->deleteUser($u);
            }
        });

        return back()->with('success', "{$count} voucher expired berhasil dihapus.");
    }
}
