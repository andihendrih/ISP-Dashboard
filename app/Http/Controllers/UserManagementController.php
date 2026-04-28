<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\Radius\Radacct;
use App\Models\Radius\Radcheck;
use App\Models\Radius\Radusergroup;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function index(Request $request): View
    {
        $type   = $request->query('type', 'all'); // all|pppoe|hotspot
        $status = $request->query('status');      // online|offline|expired
        $search = $request->query('q');

        // Source-of-truth: customer_profiles. PPPoE filtered by service_type.
        $q = CustomerProfile::query()->orderByDesc('created_at');
        if (in_array($type, ['pppoe', 'hotspot'], true)) {
            $q->where('service_type', $type);
        }
        if ($search) {
            $q->where(function ($w) use ($search) {
                $w->where('full_name', 'like', "%{$search}%")
                  ->orWhere('radius_username', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }
        $rows = $q->paginate(25)->withQueryString();

        // Determine online status from radacct in one go
        $usernames = $rows->pluck('radius_username')->filter()->all();
        $onlineSet = [];
        try {
            $onlineSet = Radacct::active()->whereIn('username', $usernames)->pluck('username')->all();
        } catch (\Throwable $e) {
            // radius unreachable; ignore
        }
        $onlineSet = array_flip($onlineSet);

        return view('users.index', [
            'rows'      => $rows,
            'type'      => $type,
            'status'    => $status,
            'search'    => $search,
            'onlineSet' => $onlineSet,
        ]);
    }

    public function show(string $username): View
    {
        $customer = CustomerProfile::where('radius_username', $username)->first();
        $check    = Radcheck::where('username', $username)->get();
        $group    = Radusergroup::where('username', $username)->value('groupname');
        $sessions = Radacct::where('username', $username)
            ->orderByDesc('acctstarttime')
            ->limit(20)
            ->get();
        $online = Radacct::active()->where('username', $username)->exists();

        return view('users.show', compact('customer', 'check', 'group', 'sessions', 'online', 'username'));
    }
}
