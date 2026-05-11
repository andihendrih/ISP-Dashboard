<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function financial(Request $request): View
    {
        [$from, $to] = $this->resolveRange($request);

        // Invoice stats in range
        $invoices = Invoice::query()
            ->whereBetween('period_start', [$from, $to])
            ->where('status', '!=', Invoice::STATUS_CANCELLED);

        $totalBilled    = (clone $invoices)->sum('total_amount');
        $totalPaid      = (clone $invoices)->sum('paid_amount');
        $totalOutstand  = max(0, $totalBilled - $totalPaid);
        $countInvoices  = (clone $invoices)->count();
        $countPaid      = (clone $invoices)->where('status', Invoice::STATUS_LUNAS)->count();

        // Revenue per month (12 bulan terakhir, terlepas dari filter)
        $monthSeries = $this->revenueSeries(12);

        // Top 10 customer (lifetime, sampai sekarang)
        $topCustomers = DB::table('invoices')
            ->join('customer_profiles', 'customer_profiles.id', '=', 'invoices.customer_profile_id')
            ->where('invoices.status', '!=', Invoice::STATUS_CANCELLED)
            ->whereBetween('invoices.period_start', [$from, $to])
            ->select(
                'customer_profiles.id',
                'customer_profiles.customer_code',
                'customer_profiles.full_name',
                DB::raw('SUM(invoices.paid_amount) AS paid_total'),
                DB::raw('COUNT(invoices.id) AS invoice_count'),
            )
            ->groupBy('customer_profiles.id', 'customer_profiles.customer_code', 'customer_profiles.full_name')
            ->orderByDesc('paid_total')
            ->limit(10)
            ->get();

        // Payment method breakdown (range)
        $methodBreakdown = Payment::query()
            ->whereBetween('paid_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()])
            ->select('method', DB::raw('SUM(amount) AS total'), DB::raw('COUNT(*) AS cnt'))
            ->groupBy('method')
            ->orderByDesc('total')
            ->get();

        // Outstanding aging (semua belum_lunas/terlambat sampai sekarang)
        $today = Carbon::today();
        $agingRows = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_BELUM_LUNAS, Invoice::STATUS_TERLAMBAT])
            ->where('paid_amount', '<', DB::raw('total_amount'))
            ->select('id', 'due_date', 'total_amount', 'paid_amount')
            ->get();

        $aging = ['0-7' => 0.0, '8-30' => 0.0, '31-60' => 0.0, '60+' => 0.0];
        foreach ($agingRows as $r) {
            $remaining = (float) $r->total_amount - (float) $r->paid_amount;
            $diff      = $r->due_date ? Carbon::parse($r->due_date)->diffInDays($today, false) : 0;
            if ($diff <= 7)       $aging['0-7']  += $remaining;
            elseif ($diff <= 30)  $aging['8-30'] += $remaining;
            elseif ($diff <= 60)  $aging['31-60']+= $remaining;
            else                  $aging['60+']  += $remaining;
        }

        $agingLabels = [
            '0-7'   => '0–7 hari',
            '8-30'  => '8–30 hari',
            '31-60' => '31–60 hari',
            '60+'   => '> 60 hari',
        ];
        $agingColors = [
            '0-7'   => 'bg-emerald-500',
            '8-30'  => 'bg-amber-500',
            '31-60' => 'bg-orange-500',
            '60+'   => 'bg-red-500',
        ];
        $agingTotal = max(1.0, array_sum($aging));

        return view('reports.financial', compact(
            'from', 'to',
            'totalBilled', 'totalPaid', 'totalOutstand', 'countInvoices', 'countPaid',
            'monthSeries', 'topCustomers', 'methodBreakdown', 'aging',
            'agingLabels', 'agingColors', 'agingTotal',
        ));
    }

    public function churn(Request $request): View
    {
        $months = (int) $request->query('months', 12);
        $months = max(3, min(24, $months));

        $start  = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $series = [];

        for ($i = 0; $i < $months; $i++) {
            $m         = (clone $start)->addMonths($i);
            $mEnd      = (clone $m)->endOfMonth();
            $newC      = CustomerProfile::whereBetween('joined_at', [$m, $mEnd])->count();
            // Churn proxy: status menjadi inactive/expired di bulan ini
            $lostC     = CustomerProfile::whereBetween('expired_at', [$m, $mEnd])
                ->orWhere(function ($q) use ($m, $mEnd) {
                    $q->where('status', 'inactive')
                      ->whereBetween('updated_at', [$m, $mEnd]);
                })
                ->count();
            $activeEnd = CustomerProfile::where('joined_at', '<=', $mEnd)
                ->where(function ($q) use ($mEnd) {
                    $q->whereNull('expired_at')->orWhere('expired_at', '>', $mEnd);
                })
                ->whereIn('status', ['active', 'isolir', 'free'])
                ->count();

            $series[] = [
                'label'      => $m->format('M Y'),
                'new'        => $newC,
                'lost'       => $lostC,
                'net'        => $newC - $lostC,
                'active_eom' => $activeEnd,
                'churn_pct'  => $activeEnd > 0 ? round(($lostC / max(1, $activeEnd)) * 100, 2) : 0,
            ];
        }

        // Status komposisi sekarang
        $statusComposition = CustomerProfile::query()
            ->select('status', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->all();

        // Pelanggan churn bulan ini
        $churnedThisMonth = CustomerProfile::query()
            ->where(function ($q) {
                $now = Carbon::now();
                $q->whereBetween('expired_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                  ->orWhere(function ($q2) use ($now) {
                      $q2->where('status', 'inactive')
                         ->whereBetween('updated_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()]);
                  });
            })
            ->orderByDesc('updated_at')
            ->limit(50)
            ->get();

        return view('reports.churn', compact('series', 'statusComposition', 'churnedThisMonth', 'months'));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->resolveRange($request);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ahnet-financial-' . $from . '_' . $to . '.csv"',
        ];

        return response()->stream(function () use ($from, $to) {
            $h = fopen('php://output', 'w');
            // Header
            fputcsv($h, [
                'Invoice Number', 'Tanggal', 'Pelanggan', 'Kode', 'Paket',
                'Periode', 'Status', 'Base', 'Discount', 'Tax', 'Total', 'Paid', 'Outstanding',
            ]);

            Invoice::query()
                ->with(['customer:id,customer_code,full_name', 'servicePlan:id,name'])
                ->whereBetween('period_start', [$from, $to])
                ->orderBy('period_start')
                ->chunk(500, function ($rows) use ($h) {
                    foreach ($rows as $inv) {
                        fputcsv($h, [
                            $inv->invoice_number,
                            optional($inv->period_start)->format('Y-m-d'),
                            $inv->customer?->full_name ?? '-',
                            $inv->customer?->customer_code ?? '-',
                            $inv->servicePlan?->name ?? '-',
                            sprintf('%04d-%02d', $inv->period_year, $inv->period_month),
                            $inv->status_label,
                            (float) $inv->base_amount,
                            (float) $inv->discount_amount,
                            (float) $inv->tax_amount,
                            (float) $inv->total_amount,
                            (float) $inv->paid_amount,
                            max(0, (float) $inv->total_amount - (float) $inv->paid_amount),
                        ]);
                    }
                });

            fclose($h);
        }, Response::HTTP_OK, $headers);
    }

    private function revenueSeries(int $months): array
    {
        $start  = Carbon::now()->startOfMonth()->subMonths($months - 1);
        $series = [];
        for ($i = 0; $i < $months; $i++) {
            $m    = (clone $start)->addMonths($i);
            $mEnd = (clone $m)->endOfMonth();

            $billed = Invoice::whereBetween('period_start', [$m, $mEnd])
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->sum('total_amount');
            $paid   = Invoice::whereBetween('period_start', [$m, $mEnd])
                ->where('status', '!=', Invoice::STATUS_CANCELLED)
                ->sum('paid_amount');

            $series[] = [
                'label'  => $m->format('M Y'),
                'billed' => (float) $billed,
                'paid'   => (float) $paid,
            ];
        }
        return $series;
    }

    private function resolveRange(Request $request): array
    {
        $from = $request->query('from');
        $to   = $request->query('to');

        if (!$from || !$to) {
            // default: 12 bulan terakhir
            $from = Carbon::now()->startOfMonth()->subMonths(11)->toDateString();
            $to   = Carbon::now()->endOfMonth()->toDateString();
        }
        return [$from, $to];
    }
}
