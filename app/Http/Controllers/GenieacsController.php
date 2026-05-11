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
            'ssid'      => ['nullable', 'string', 'max:64'],
            'ssid_path' => ['nullable', 'string'],
        ]);
        if (!$request->filled('ssid')) {
            return back()->with('info', 'SSID kosong — tidak ada yang di-update.');
        }
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
            'password'      => ['nullable', 'string', 'min:8', 'max:64'],
            'password_path' => ['nullable', 'string'],
        ]);
        if (!$request->filled('password')) {
            return back()->with('info', 'Password kosong — tidak ada yang di-update.');
        }
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
            'username_path' => ['nullable', 'string'],
            'password_path' => ['nullable', 'string'],
            'vlan_path'     => ['nullable', 'string'],
            'username'      => ['nullable', 'string', 'max:128'],
            'password'      => ['nullable', 'string', 'max:128'],
            'vlan'          => ['nullable', 'integer', 'between:0,4094'],
        ]);
        try {
            $params = [];
            if ($request->filled('username') && !empty($data['username_path'])) {
                $params[] = [$data['username_path'], $data['username'], 'xsd:string'];
            }
            if ($request->filled('password') && !empty($data['password_path'])) {
                $params[] = [$data['password_path'], $data['password'], 'xsd:string'];
            }
            if ($request->filled('vlan') && !empty($data['vlan_path'])) {
                $params[] = [$data['vlan_path'], (int) $data['vlan'], 'xsd:unsignedInt'];
            }
            if (empty($params)) {
                return back()->with('info', 'Tidak ada field yang berubah.');
            }
            $this->genieacs->setParameters($device->device_id, $params);
            $changed = array_map(fn($p) => $this->fieldLabel($p[0]), $params);
            return back()->with('success', 'PPPoE di-update: ' . implode(', ', $changed));
        } catch (\Throwable $e) {
            return back()->with('error', 'Set PPPoE gagal: ' . $e->getMessage());
        }
    }

    public function setWanIp(Request $request, GenieacsDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'ip_path'      => ['nullable', 'string'],
            'subnet_path'  => ['nullable', 'string'],
            'gateway_path' => ['nullable', 'string'],
            'vlan_path'    => ['nullable', 'string'],
            'external_ip'  => ['nullable', 'ip'],
            'subnet_mask'  => ['nullable', 'ip'],
            'gateway'      => ['nullable', 'ip'],
            'vlan'         => ['nullable', 'integer', 'between:0,4094'],
        ]);
        try {
            $params = [];
            if ($request->filled('external_ip') && !empty($data['ip_path'])) {
                $params[] = [$data['ip_path'], $data['external_ip'], 'xsd:string'];
            }
            if ($request->filled('subnet_mask') && !empty($data['subnet_path'])) {
                $params[] = [$data['subnet_path'], $data['subnet_mask'], 'xsd:string'];
            }
            if ($request->filled('gateway') && !empty($data['gateway_path'])) {
                $params[] = [$data['gateway_path'], $data['gateway'], 'xsd:string'];
            }
            if ($request->filled('vlan') && !empty($data['vlan_path'])) {
                $params[] = [$data['vlan_path'], (int) $data['vlan'], 'xsd:unsignedInt'];
            }
            if (empty($params)) {
                return back()->with('info', 'Tidak ada field yang berubah.');
            }
            $this->genieacs->setParameters($device->device_id, $params);
            $changed = array_map(fn($p) => $this->fieldLabel($p[0]), $params);
            return back()->with('success', 'WAN IP di-update: ' . implode(', ', $changed));
        } catch (\Throwable $e) {
            return back()->with('error', 'Set WAN IP gagal: ' . $e->getMessage());
        }
    }

    private function fieldLabel(string $path): string
    {
        $map = [
            'PPPoEUsername' => 'Username', 'PPPoEPassword' => 'Password',
            'WANPPPVlanID'  => 'VLAN', 'VLANIDTR' => 'VLAN',
            'MGMTIP' => 'External IP', 'MGMTMASK' => 'Subnet', 'MGMTGW' => 'Gateway',
        ];
        foreach ($map as $needle => $label) {
            if (str_contains($path, $needle)) return $label;
        }
        return basename(str_replace('.', '/', $path));
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

    public function setWifiSecurity(Request $request, GenieacsDevice $device): RedirectResponse
    {
        $data = $request->validate([
            'security_path' => ['required', 'string'],
            'security'      => ['required', 'in:None,Basic,WPA,11i,WPAand11i'],
        ]);
        try {
            $this->genieacs->setParameter($device->device_id, $data['security_path'], $data['security'], 'xsd:string');
            return back()->with('success', "WiFi security di-set ke {$data['security']}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Set WiFi security gagal: ' . $e->getMessage());
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
