<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\DeviceMikrotik;
use App\Models\Radius\Radcheck;
use App\Models\Radius\Radusergroup;
use App\Services\MikrotikService;
use App\Services\RadiusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PppoeController extends Controller
{
    public function __construct(
        private RadiusService $radius,
        private MikrotikService $mikrotik
    ) {}

    public function index(): View
    {
        try {
            $rows = Radcheck::query()
                ->where('attribute', 'Cleartext-Password')
                ->orderByDesc('id')
                ->paginate(25);
        } catch (\Throwable $e) {
            Log::warning('PPPoE index radius read failed: ' . $e->getMessage());
            $rows = collect();
        }

        return view('pppoe.index', [
            'rows'    => $rows,
            'devices' => DeviceMikrotik::where('is_active', true)->get(),
        ]);
    }

    public function create(): View
    {
        return view('pppoe.create', [
            'devices' => DeviceMikrotik::where('is_active', true)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name'         => ['required', 'string', 'max:120'],
            'username'          => ['nullable', 'string', 'max:64'],
            'password'          => ['nullable', 'string', 'max:64'],
            'group'             => ['nullable', 'string', 'max:64'],
            'rate_limit'        => ['nullable', 'string', 'max:64'],
            'simultaneous_use'  => ['nullable', 'integer', 'min:1', 'max:10'],
            'mikrotik_device_id'=> ['nullable', 'integer', 'exists:devices_mikrotik,id'],
            'mikrotik_profile'  => ['nullable', 'string', 'max:64'],
            'sync_to_mikrotik'  => ['nullable', 'boolean'],
        ]);

        $username = !empty($data['username'] ?? null) ? $data['username'] : $this->radius->generatePppoeUsername($data['full_name']);
        $password = !empty($data['password'] ?? null) ? $data['password'] : $this->radius->generatePppoePassword();

        $this->radius->createPppoeUser(
            $username,
            $password,
            $data['group'] ?? null,
            $data['rate_limit'] ?? null,
            (int) ($data['simultaneous_use'] ?? 1)
        );

        // Persist a customer record
        CustomerProfile::create([
            'customer_code'      => 'C' . now()->format('ymdHis'),
            'full_name'          => $data['full_name'],
            'package'            => $data['group'] ?? config('ahnet.radius.default_pppoe_group'),
            'rate_limit'         => $data['rate_limit'] ?? null,
            'service_type'       => 'pppoe',
            'status'             => 'active',
            'radius_username'    => $username,
            'mikrotik_device_id' => $data['mikrotik_device_id'] ?? null,
            'joined_at'          => now()->toDateString(),
        ]);

        if (!empty($data['sync_to_mikrotik']) && !empty($data['mikrotik_device_id'])) {
            try {
                $device = DeviceMikrotik::findOrFail($data['mikrotik_device_id']);
                $this->mikrotik->syncPppoeSecret(
                    $device,
                    $username,
                    $password,
                    $data['mikrotik_profile'] ?: 'default'
                );
            } catch (\Throwable $e) {
                Log::warning('Mikrotik sync failed: ' . $e->getMessage());
                return redirect()
                    ->route('pppoe.index')
                    ->with('warning', "User dibuat di RADIUS tapi sync ke Mikrotik gagal: {$e->getMessage()}");
            }
        }

        return redirect()
            ->route('pppoe.index')
            ->with('success', "User PPPoE {$username} berhasil dibuat. Password: {$password}");
    }

    public function bulkCreate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'count'      => ['required', 'integer', 'min:1', 'max:1000'],
            'name_seed'  => ['required', 'string', 'max:60'],
            'group'      => ['nullable', 'string', 'max:64'],
            'rate_limit' => ['nullable', 'string', 'max:64'],
        ]);

        $created = [];
        for ($i = 1; $i <= $data['count']; $i++) {
            $username = $this->radius->generatePppoeUsername("{$data['name_seed']}{$i}");
            $password = $this->radius->generatePppoePassword();
            $this->radius->createPppoeUser($username, $password, $data['group'] ?? null, $data['rate_limit'] ?? null);
            $created[] = ['username' => $username, 'password' => $password];
        }

        $request->session()->flash('bulk_created', $created);
        return redirect()->route('pppoe.index')->with('success', count($created) . ' user PPPoE dibuat.');
    }

    public function destroy(string $username): RedirectResponse
    {
        $this->radius->deleteUser($username);
        CustomerProfile::where('radius_username', $username)->delete();
        return back()->with('success', "User {$username} dihapus.");
    }

    public function resetPassword(Request $request, string $username): RedirectResponse
    {
        $newPwd = $request->input('password') ?: $this->radius->generatePppoePassword();
        $this->radius->setPassword($username, $newPwd);
        return back()->with('success', "Password {$username} di-reset menjadi: {$newPwd}");
    }
}
