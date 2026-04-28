<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\Radius\Radacct;
use App\Services\RadiusService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private RadiusService $radius) {}

    public function index(Request $request): View
    {
        $year = (int) $request->query('year', Carbon::now()->year);

        // Top stat cards
        $totalPelanggan   = CustomerProfile::count();
        $totalLayanan     = CustomerProfile::whereIn('status', ['active', 'free', 'isolir'])->count();
        $startMonth       = Carbon::now()->startOfMonth();
        $pelangganBaru    = CustomerProfile::where('created_at', '>=', $startMonth)->count();
        $isolir           = CustomerProfile::where('status', 'isolir')->count();

        // Donut: status komposisi
        $statusBuckets = [
            'aktif'     => CustomerProfile::where('status', 'active')->count(),
            'free'      => CustomerProfile::where('status', 'free')->count(),
            'menunggu'  => CustomerProfile::where('status', 'pending')->count(),
            'non_aktif' => CustomerProfile::whereIn('status', ['inactive', 'isolir'])->count(),
        ];

        // Bar: pelanggan baru per bulan tahun ini (dari customer_profiles)
        $perMonth = array_fill(1, 12, 0);
        $rows = CustomerProfile::query()
            ->whereYear('created_at', $year)
            ->get(['created_at']);
        foreach ($rows as $row) {
            $m = (int) Carbon::parse($row->created_at)->month;
            $perMonth[$m] = ($perMonth[$m] ?? 0) + 1;
        }

        // Kesehatan layanan
        try {
            $billingActive    = CustomerProfile::where('status', 'active')->count();
            $billingNonActive = CustomerProfile::where('status', 'inactive')->count();
            $bulanLalu        = CustomerProfile::whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])->count();
        } catch (\Throwable $e) {
            $billingActive = $billingNonActive = $bulanLalu = 0;
        }

        // Pelanggan terbaru
        $pelangganTerbaru = CustomerProfile::orderByDesc('created_at')->limit(8)->get();

        // Online dari radacct (best-effort, bisa gagal kalau radius down)
        try {
            $online = Radacct::active()->distinct('username')->count('username');
        } catch (\Throwable $e) {
            $online = null;
        }

        return view('dashboard.index', [
            'year'            => $year,
            'totalPelanggan'  => $totalPelanggan,
            'totalLayanan'    => $totalLayanan,
            'pelangganBaru'   => $pelangganBaru,
            'isolir'          => $isolir,
            'statusBuckets'   => $statusBuckets,
            'perMonth'        => array_values($perMonth),
            'kesehatan'       => [
                'billing_active'     => $billingActive,
                'billing_non_active' => $billingNonActive,
                'pelanggan_baru_lalu'=> $bulanLalu,
            ],
            'pelangganTerbaru' => $pelangganTerbaru,
            'online'           => $online,
        ]);
    }
}
