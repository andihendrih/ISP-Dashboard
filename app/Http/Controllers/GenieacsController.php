<?php

namespace App\Http\Controllers;

use App\Models\GenieacsDevice;
use App\Services\GenieacsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GenieacsController extends Controller
{
    public function __construct(private GenieacsService $genieacs) {}

    public function index(Request $request): View
    {
        // IMPORTANT: never include `raw` here — it's a 50-200KB JSON blob
        // and adding it to filesort buffer triggers MySQL OOM (HY001 1038).
        $q = GenieacsDevice::query()
            ->select([
                'id', 'device_id', 'serial_number', 'manufacturer', 'product_class',
                'model_name', 'software_version', 'hardware_version', 'ssid', 'ip',
                'tag', 'status', 'last_inform_at', 'pppoe_username', 'rx_power',
                'wifi_ssid_24', 'wifi_ssid_5g', 'wan_external_ip',
            ]);

        if ($status = $request->query('status')) {
            if (in_array($status, ['online', 'offline', 'unknown'], true)) {
                $q->where('status', $status);
            }
        }
        if ($model = $request->query('model')) {
            $q->where('product_class', $model);
        }
        if ($search = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($search) {
                $w->where('serial_number', 'like', "%{$search}%")
                  ->orWhere('tag', 'like', "%{$search}%")
                  ->orWhere('model_name', 'like', "%{$search}%")
                  ->orWhere('product_class', 'like', "%{$search}%")
                  ->orWhere('ip', 'like', "%{$search}%")
                  ->orWhere('ssid', 'like', "%{$search}%")
                  ->orWhere('pppoe_username', 'like', "%{$search}%");
            });
        }

        $devices = $q->orderByDesc('last_inform_at')->paginate(50)->withQueryString();

        $stats = [
            'total'   => GenieacsDevice::count(),
            'online'  => GenieacsDevice::where('status', 'online')->count(),
            'offline' => GenieacsDevice::where('status', 'offline')->count(),
        ];

        $modelStats = GenieacsDevice::query()
            ->selectRaw("
                COALESCE(NULLIF(product_class, ''), 'Unknown') as product_class,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online,
                SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline
            ")
            ->groupBy('product_class')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return view('genieacs.index', compact('devices', 'stats', 'modelStats'));
    }

    public function show(GenieacsDevice $device): View
    {
        $params = $device->params();

        $sessions = [];
        $username = $params['pppoe']['username'] ?? null;
        if ($username) {
            try {
                $sessions = DB::connection('radius')->table('radacct')
                    ->where('username', $username)
                    ->orderByDesc('acctstarttime')
                    ->limit(20)
                    ->get(['acctstarttime', 'acctstoptime', 'framedipaddress',
                           'acctsessiontime', 'acctinputoctets', 'acctoutputoctets',
                           'nasipaddress', 'callingstationid'])
                    ->toArray();
            } catch (\Throwable $e) {
                $sessions = [];
            }
        }

        return view('genieacs.show', compact('device', 'params', 'sessions'));
    }

    public function sync(): RedirectResponse
    {
        try {
            $count = $this->genieacs->syncDevices();
            return back()->with('success', "{$count} device di-sync dari GenieACS.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Sync GenieACS gagal: ' . $e->getMessage());
        }
    }

    public function reboot(GenieacsDevice $device): RedirectResponse
    {
        try {
            $this->genieacs->reboot($device->device_id);
            return back()->with('success', "Reboot dikirim ke {$device->serial_number}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Reboot gagal: ' . $e->getMessage());
        }
    }

    public function factoryReset(GenieacsDevice $device): RedirectResponse
    {
        try {
            $this->genieacs->factoryReset($device->device_id);
            return back()->with('success', "Factory reset dikirim ke {$device->serial_number}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Factory reset gagal: ' . $e->getMessage());
        }
    }

    public function refresh(GenieacsDevice $device): RedirectResponse
    {
        try {
            $this->genieacs->refreshDevice($device->device_id);
            return back()->with('success', "Refresh task dikirim ke {$device->serial_number}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Refresh gagal: ' . $e->getMessage());
        }
    }

    public function setSsid(Request $request, GenieacsDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'ssid'      => ['required', 'string', 'max:64'],
            'ssid_path' => ['nullable', 'string'],
        ]);
        try {
            $this->genieacs->setSsid($device->device_id, $data['ssid'], $data['ssid_path'] ?? null);
            return back()->with('success', "SSID di-set ke '{$data['ssid']}'.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Set SSID gagal: ' . $e->getMessage());
        }
    }

    public function setPassword(Request $request, GenieacsDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'password'      => ['required', 'string', 'min:8', 'max:64'],
            'password_path' => ['nullable', 'string'],
        ]);
        try {
            $this->genieacs->setWifiPassword($device->device_id, $data['password'], $data['password_path'] ?? null);
            return back()->with('success', 'Password WiFi di-update.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Set password gagal: ' . $e->getMessage());
        }
    }

    public function setPppoe(Request $request, GenieacsDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'username_path' => ['required', 'string'],
            'password_path' => ['required', 'string'],
            'username'      => ['required', 'string', 'max:128'],
            'password'      => ['required', 'string', 'max:128'],
        ]);
        try {
            $this->genieacs->setPppoeCredentials(
                $device->device_id, $data['username_path'], $data['password_path'],
                $data['username'], $data['password']
            );
            return back()->with('success', 'Kredensial PPPoE di-update.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Set PPPoE gagal: ' . $e->getMessage());
        }
    }

    public function setWanIp(Request $request, GenieacsDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'ip_path'      => ['required', 'string'],
            'subnet_path'  => ['required', 'string'],
            'gateway_path' => ['required', 'string'],
            'external_ip'  => ['required', 'ip'],
            'subnet_mask'  => ['required', 'ip'],
            'gateway'      => ['required', 'ip'],
        ]);
        try {
            $this->genieacs->setWanIp(
                $device->device_id,
                $data['ip_path'], $data['subnet_path'], $data['gateway_path'],
                $data['external_ip'], $data['subnet_mask'], $data['gateway']
            );
            return back()->with('success', 'WAN IP di-update.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Set WAN IP gagal: ' . $e->getMessage());
        }
    }

    public function suspendWan(Request $request, GenieacsDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'enable_path' => ['required', 'string'],
            'enable'      => ['required', 'boolean'],
        ]);
        try {
            $this->genieacs->setWanEnable($device->device_id, $data['enable_path'], (bool) $data['enable']);
            $action = $data['enable'] ? 'di-aktifkan' : 'di-suspend';
            return back()->with('success', "WAN {$action}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Suspend WAN gagal: ' . $e->getMessage());
        }
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $rows = GenieacsDevice::query()
            ->select(['serial_number', 'manufacturer', 'product_class', 'model_name',
                      'software_version', 'hardware_version', 'ssid', 'ip', 'tag',
                      'status', 'last_inform_at'])
            ->orderByDesc('last_inform_at')
            ->limit(5000)
            ->get();

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Serial', 'Manufacturer', 'Product Class', 'Model',
                'SW Version', 'HW Version', 'SSID', 'IP', 'Tag', 'Status', 'Last Inform']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->serial_number, $r->manufacturer, $r->product_class, $r->model_name,
                    $r->software_version, $r->hardware_version, $r->ssid, $r->ip,
                    $r->tag, $r->status, optional($r->last_inform_at)->toDateTimeString(),
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="genieacs-devices-' . date('Ymd-His') . '.csv"',
        ]);
    }
}
