<?php

namespace App\Http\Controllers;

use App\Models\HotspotVoucher;
use App\Services\RadiusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HotspotController extends Controller
{
    public function __construct(private RadiusService $radius) {}

    public function index(Request $request): View
    {
        $q = HotspotVoucher::query()->orderByDesc('id');
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        if ($batch = $request->query('batch_id')) {
            $q->where('batch_id', $batch);
        }
        return view('hotspot.index', ['rows' => $q->paginate(50)]);
    }

    public function create(): View
    {
        return view('hotspot.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'count'         => ['required', 'integer', 'min:1', 'max:5000'],
            'profile'       => ['nullable', 'string', 'max:64'],
            'rate_limit'    => ['nullable', 'string', 'max:64'],
            'valid_minutes' => ['nullable', 'integer', 'min:1', 'max:1440000'],
            'length'        => ['nullable', 'integer', 'min:4', 'max:16'],
        ]);

        $batchId = 'B' . now()->format('ymdHis') . '-' . Str::lower(Str::random(4));
        $count   = (int) $data['count'];
        $length  = (int) ($data['length'] ?? 5);

        for ($i = 0; $i < $count; $i++) {
            $code = $this->radius->generateVoucherCode($length);
            $this->radius->createHotspotVoucher(
                $code,
                $data['profile'] ?? null,
                $data['rate_limit'] ?? null,
                isset($data['valid_minutes']) ? (int) $data['valid_minutes'] : null,
                $request->user()?->id,
                $batchId
            );
        }

        return redirect()
            ->route('hotspot.index', ['batch_id' => $batchId])
            ->with('success', "{$count} voucher berhasil dibuat (batch {$batchId}).");
    }

    public function destroy(int $id): RedirectResponse
    {
        $voucher = HotspotVoucher::findOrFail($id);
        $this->radius->deleteUser($voucher->code);
        $voucher->delete();
        return back()->with('success', 'Voucher dihapus.');
    }
}
