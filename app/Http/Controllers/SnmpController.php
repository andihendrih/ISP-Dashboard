<?php

namespace App\Http\Controllers;

use App\Models\DeviceMikrotik;
use App\Services\SnmpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SnmpController extends Controller
{
    public function __construct(private SnmpService $snmp) {}

    public function index(Request $request): View
    {
        $devices = DeviceMikrotik::where('is_active', true)->orderBy('name')->get();
        $deviceId = (int) $request->query('device_id', $devices->first()->id ?? 0);
        $device = $deviceId ? DeviceMikrotik::find($deviceId) : null;

        $latest = $device ? $this->snmp->latestPerInterface($device) : collect();

        return view('snmp.index', compact('devices', 'device', 'latest'));
    }

    public function poll(int $id): RedirectResponse
    {
        $device = DeviceMikrotik::findOrFail($id);
        try {
            $rows = $this->snmp->pollDevice($device);
            return back()->with('success', count($rows) . ' interface tersimpan.');
        } catch (\Throwable $e) {
            return back()->with('error', 'SNMP poll gagal: ' . $e->getMessage());
        }
    }

    public function history(Request $request, int $id): JsonResponse
    {
        $device  = DeviceMikrotik::findOrFail($id);
        $ifIndex = (int) $request->query('if_index', 1);
        $minutes = (int) $request->query('minutes', 60);
        $rows = $this->snmp->history($device, $ifIndex, $minutes);

        return response()->json([
            'labels' => $rows->pluck('polled_at')->map(fn($t) => $t->format('H:i:s')),
            'in'     => $rows->pluck('in_bps'),
            'out'    => $rows->pluck('out_bps'),
        ]);
    }
}
