<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\Device;
use App\Models\DeviceAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(Request $request): View
    {
        $q = Device::query()->with('customer:id,customer_code,full_name');

        if ($s = trim((string) $request->query('q'))) {
            $q->where(function ($w) use ($s) {
                $w->where('serial_number', 'like', "%{$s}%")
                  ->orWhere('mac_address', 'like', "%{$s}%")
                  ->orWhere('brand', 'like', "%{$s}%")
                  ->orWhere('model', 'like', "%{$s}%");
            });
        }
        if ($type = $request->query('type')) {
            $q->where('type', $type);
        }
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }

        $stats = [
            'total'    => Device::count(),
            'stock'    => Device::where('status', 'stock')->count(),
            'assigned' => Device::where('status', 'assigned')->count(),
            'rusak'    => Device::where('status', 'rusak')->count(),
        ];

        return view('devices.index', [
            'rows'  => $q->orderByDesc('created_at')->paginate(25)->withQueryString(),
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        return view('devices.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['status'] = $data['status'] ?? 'stock';

        $device = Device::create($data);

        DeviceAssignment::create([
            'device_id'     => $device->id,
            'technician_id' => auth()->id(),
            'action'        => 'mark_stock',
            'acted_at'      => now(),
            'notes'         => 'Perangkat baru masuk inventory',
        ]);

        return redirect()->route('devices.show', $device)->with('success', 'Perangkat ditambahkan.');
    }

    public function show(Device $device): View
    {
        $device->load(['customer', 'assignments.customer:id,customer_code,full_name', 'assignments.technician:id,name']);
        $customers = CustomerProfile::select('id', 'customer_code', 'full_name')
            ->orderBy('full_name')->limit(500)->get();
        return view('devices.show', compact('device', 'customers'));
    }

    public function edit(Device $device): View
    {
        return view('devices.edit', compact('device'));
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $data = $this->validated($request, $device->id);
        $device->update($data);
        return redirect()->route('devices.show', $device)->with('success', 'Perangkat diperbarui.');
    }

    public function destroy(Device $device): RedirectResponse
    {
        $device->delete();
        return redirect()->route('devices.index')->with('success', 'Perangkat dihapus.');
    }

    /**
     * Assign device to customer (install).
     */
    public function assign(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customer_profiles,id'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($device, $data) {
            $device->update([
                'customer_id' => $data['customer_id'],
                'status'      => 'assigned',
            ]);
            DeviceAssignment::create([
                'device_id'     => $device->id,
                'customer_id'   => $data['customer_id'],
                'technician_id' => auth()->id(),
                'action'        => 'install',
                'acted_at'      => now(),
                'notes'         => $data['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Perangkat dipasang ke pelanggan.');
    }

    /**
     * Change status (return / repair / mark_stock / mark_lost / retire).
     */
    public function transition(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate([
            'action' => ['required', 'in:return,repair,mark_stock,mark_lost,retire'],
            'notes'  => ['nullable', 'string', 'max:500'],
        ]);

        $nextStatus = match ($data['action']) {
            'return'     => 'stock',
            'repair'     => 'rusak',
            'mark_stock' => 'stock',
            'mark_lost'  => 'hilang',
            'retire'     => 'retired',
        };

        DB::transaction(function () use ($device, $data, $nextStatus) {
            $prevCustomer = $device->customer_id;
            $device->update([
                'status'      => $nextStatus,
                'customer_id' => in_array($data['action'], ['return', 'mark_stock', 'repair', 'mark_lost', 'retire'])
                    ? null : $device->customer_id,
            ]);
            DeviceAssignment::create([
                'device_id'     => $device->id,
                'customer_id'   => $prevCustomer,
                'technician_id' => auth()->id(),
                'action'        => $data['action'],
                'acted_at'      => now(),
                'notes'         => $data['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Status perangkat diperbarui.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $uniqueRule = 'unique:devices_inventory,serial_number';
        if ($ignoreId) {
            $uniqueRule .= ',' . $ignoreId;
        }

        return $request->validate([
            'type'               => ['required', 'in:onu,router,switch,ap,radio,cable,other'],
            'brand'              => ['nullable', 'string', 'max:64'],
            'model'              => ['nullable', 'string', 'max:64'],
            'serial_number'      => ['required', 'string', 'max:64', $uniqueRule],
            'mac_address'        => ['nullable', 'string', 'max:32'],
            'status'             => ['nullable', 'in:stock,assigned,rusak,hilang,retired'],
            'purchase_price'     => ['nullable', 'numeric', 'min:0'],
            'purchased_at'       => ['nullable', 'date'],
            'warehouse_location' => ['nullable', 'string', 'max:120'],
            'notes'              => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
