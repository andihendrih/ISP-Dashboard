<?php

namespace App\Http\Controllers;

use App\Models\DeviceMikrotik;
use App\Services\MikrotikService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MikrotikController extends Controller
{
    public function __construct(private MikrotikService $mikrotik) {}

    public function index(): View
    {
        return view('mikrotik.index', [
            'devices' => DeviceMikrotik::orderBy('name')->get(),
        ]);
    }

    public function show(int $id): View
    {
        $device = DeviceMikrotik::findOrFail($id);
        $error  = null;
        $info   = null;
        $active = [];
        $profiles = [];
        try {
            $info     = $this->mikrotik->ping($device);
            $active   = $this->mikrotik->activeSessions($device);
            $profiles = $this->mikrotik->listProfiles($device);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        return view('mikrotik.show', compact('device', 'info', 'active', 'profiles', 'error'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'host'           => ['required', 'string', 'max:120'],
            'api_port'       => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username'       => ['required', 'string', 'max:64'],
            'password'       => ['required', 'string', 'max:120'],
            'use_ssl'        => ['nullable', 'boolean'],
            'snmp_community' => ['nullable', 'string', 'max:64'],
        ]);

        $device = DeviceMikrotik::create($data);
        try {
            $this->mikrotik->refreshDeviceMetadata($device);
        } catch (\Throwable) { /* best-effort */ }

        return redirect()->route('mikrotik.show', $device->id)
            ->with('success', 'Device Mikrotik ditambahkan.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $device = DeviceMikrotik::findOrFail($id);
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:120'],
            'host'           => ['required', 'string', 'max:120'],
            'api_port'       => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username'       => ['required', 'string', 'max:64'],
            'password'       => ['nullable', 'string', 'max:120'],
            'use_ssl'        => ['nullable', 'boolean'],
            'snmp_community' => ['nullable', 'string', 'max:64'],
            'is_active'      => ['nullable', 'boolean'],
        ]);
        if (empty($data['password'])) unset($data['password']);
        $device->update($data);
        return back()->with('success', 'Device diperbarui.');
    }

    public function destroy(int $id): RedirectResponse
    {
        DeviceMikrotik::findOrFail($id)->delete();
        return redirect()->route('mikrotik.index')->with('success', 'Device dihapus.');
    }

    public function disconnect(Request $request, int $id): RedirectResponse
    {
        $device = DeviceMikrotik::findOrFail($id);
        $username = (string) $request->input('username');
        try {
            $ok = $this->mikrotik->disconnectPppoeUser($device, $username);
            return back()->with('success', $ok ? "Session {$username} di-disconnect." : "User {$username} tidak punya session aktif.");
        } catch (\Throwable $e) {
            return back()->with('error', "Gagal disconnect: {$e->getMessage()}");
        }
    }
}
