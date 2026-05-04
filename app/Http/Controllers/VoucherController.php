<?php

namespace App\Http\Controllers;

use App\Models\Radius\Radacct;
use App\Models\Radius\Radcheck;
use App\Models\Radius\Radusergroup;
use App\Models\VoucherBatch;
use App\Services\RadiusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    /** Halaman generator + history batch. */
    public function generateForm(): View
    {
        $groups   = $this->safeHotspotGroups();
        $batches  = VoucherBatch::orderByDesc('id')->limit(20)->get();
        return view('vouchers.generate', compact('groups', 'batches'));
    }

    /** Generate batch voucher → insert ke RADIUS + simpan metadata batch. */
    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'profile'     => 'required|string|max:64',
            'count'       => 'required|integer|min:1|max:300',
            'code_length' => 'required|integer|min:4|max:12',
            'prefix'      => 'nullable|string|max:8|alpha_dash',
            'expires_in_days' => 'nullable|integer|min:0|max:3650',
            'label'       => 'nullable|string|max:120',
        ]);

        $count   = (int) $data['count'];
        $len     = (int) $data['code_length'];
        $prefix  = strtoupper((string) ($data['prefix'] ?? ''));
        $profile = (string) $data['profile'];
        $days    = (int) ($data['expires_in_days'] ?? 0);
        $expires = $days > 0 ? Carbon::now()->addDays($days) : null;

        // Generate codes (avoid collision with existing radcheck.username)
        $existing = Radcheck::where('attribute', 'Cleartext-Password')->pluck('username')->flip();
        $codes    = [];
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // skip I,O,0,1 (mudah salah baca)

        while (count($codes) < $count) {
            $code = $prefix . $this->randomCode($len, $alphabet);
            if (isset($existing[$code]) || in_array($code, $codes, true)) continue;
            $codes[] = $code;
        }

        DB::connection('radius')->transaction(function () use ($codes, $profile, $expires) {
            foreach ($codes as $code) {
                Radcheck::create([
                    'username'  => $code,
                    'attribute' => 'Cleartext-Password',
                    'op'        => ':=',
                    'value'     => $code,
                ]);
                if ($expires) {
                    Radcheck::create([
                        'username'  => $code,
                        'attribute' => 'Expiration',
                        'op'        => ':=',
                        'value'     => $expires->format('M j Y H:i:s'),
                    ]);
                }
                Radusergroup::create([
                    'username'  => $code,
                    'groupname' => $profile,
                    'priority'  => 1,
                ]);
            }
        });

        $batch = VoucherBatch::create([
            'label'       => $data['label'] ?? null,
            'profile'     => $profile,
            'count'       => $count,
            'prefix'      => $prefix ?: null,
            'code_length' => $len,
            'expires_at'  => $expires?->toDateString(),
            'codes'       => $codes,
            'created_by'  => Auth::id(),
        ]);

        return redirect()->route('vouchers.batch.print', $batch->id)
            ->with('success', "{$count} voucher berhasil di-generate. Klik tombol Cetak untuk print A4.");
    }

    public function batchPrint(VoucherBatch $batch): View
    {
        return view('vouchers.print', ['batch' => $batch]);
    }

    public function batchShow(VoucherBatch $batch): View
    {
        return view('vouchers.batch-show', ['batch' => $batch]);
    }

    public function batchDestroy(VoucherBatch $batch): RedirectResponse
    {
        // Hapus voucher dari RADIUS juga
        $codes = (array) $batch->codes;
        DB::connection('radius')->transaction(function () use ($codes) {
            foreach ($codes as $code) {
                $this->radius->deleteUser($code);
            }
        });
        $batch->delete();
        return redirect()->route('vouchers.generate-form')
            ->with('success', "Batch '{$batch->label}' beserta " . count($codes) . " voucher dihapus.");
    }

    /* --- helpers --- */

    /** Daftar grup hotspot (Hotspot* + HS_*) buat dropdown profile generator. */
    protected function safeHotspotGroups(): array
    {
        try {
            $all = $this->radius->listGroups(false);
            return $all->filter(fn ($g) => RadiusService::isVoucherGroup($g))->values()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function randomCode(int $len, string $alphabet): string
    {
        $max = strlen($alphabet) - 1;
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $alphabet[random_int(0, $max)];
        }
        return $out;
    }
}
