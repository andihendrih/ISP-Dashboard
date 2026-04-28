<?php

namespace App\Http\Controllers;

use App\Models\GenieacsDevice;
use App\Services\GenieacsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenieacsController extends Controller
{
    public function __construct(private GenieacsService $genieacs) {}

    public function index(): View
    {
        $devices = GenieacsDevice::orderByDesc('last_inform_at')->paginate(50);
        return view('genieacs.index', ['devices' => $devices]);
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

    public function reboot(string $deviceId): RedirectResponse
    {
        try {
            $this->genieacs->reboot($deviceId);
            return back()->with('success', "Reboot dikirim ke {$deviceId}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Reboot gagal: ' . $e->getMessage());
        }
    }

    public function setSsid(Request $request, string $deviceId): RedirectResponse
    {
        $data = $request->validate(['ssid' => ['required', 'string', 'max:64']]);
        try {
            $this->genieacs->setSsid($deviceId, $data['ssid']);
            return back()->with('success', "SSID di-set ke '{$data['ssid']}' untuk {$deviceId}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Set SSID gagal: ' . $e->getMessage());
        }
    }

    public function setPassword(Request $request, string $deviceId): RedirectResponse
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'max:64']]);
        try {
            $this->genieacs->setWifiPassword($deviceId, $data['password']);
            return back()->with('success', "Password WiFi di-update untuk {$deviceId}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Set password gagal: ' . $e->getMessage());
        }
    }

    public function refresh(string $deviceId): RedirectResponse
    {
        try {
            $this->genieacs->refreshDevice($deviceId);
            return back()->with('success', "Refresh task dikirim ke {$deviceId}.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Refresh gagal: ' . $e->getMessage());
        }
    }
}
