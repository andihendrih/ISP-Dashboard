@extends('layouts.app')
@section('title','Laporan Keuangan')

@section('content')
<div class="flex items-start justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Laporan Keuangan</h1>
        <p class="text-sm text-ink/55">Revenue, top customer, breakdown pembayaran &amp; outstanding aging.</p>
    </div>
    <a href="{{ route('reports.financial.export', request()->query()) }}" class="px-4 py-2 bg-ink text-cream rounded-xl text-sm font-semibold hover:brightness-95">⬇ Export CSV</a>
</div>

<form method="GET" class="bg-white border border-cream-deep/60 rounded-2xl p-3 mb-4 flex gap-2 items-end">
    <div>
        <label class="text-xs text-ink/55">Dari</label>
        <input type="date" name="from" value="{{ $from }}" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
    </div>
    <div>
        <label class="text-xs text-ink/55">Sampai</label>
        <input type="date" name="to" value="{{ $to }}" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
    </div>
    <button class="px-4 py-2 bg-accent text-ink rounded-xl text-sm font-semibold">Filter</button>
    <a href="{{ route('reports.financial') }}" class="px-3 py-2 text-sm text-ink/55 hover:bg-cream-deep/60 rounded-xl">Reset</a>
</form>

{{-- Stat tiles --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55 uppercase tracking-wide">Total Tagihan</div>
        <div class="text-2xl font-bold mt-1">Rp {{ number_format($totalBilled, 0, ',', '.') }}</div>
        <div class="text-xs text-ink/45 mt-0.5">{{ $countInvoices }} invoice</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55 uppercase tracking-wide">Total Diterima</div>
        <div class="text-2xl font-bold mt-1 text-emerald-600">Rp {{ number_format($totalPaid, 0, ',', '.') }}</div>
        <div class="text-xs text-ink/45 mt-0.5">{{ $countPaid }} lunas</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55 uppercase tracking-wide">Outstanding</div>
        <div class="text-2xl font-bold mt-1 text-amber-600">Rp {{ number_format($totalOutstand, 0, ',', '.') }}</div>
        <div class="text-xs text-ink/45 mt-0.5">{{ $countInvoices - $countPaid }} belum lunas</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55 uppercase tracking-wide">Collection Rate</div>
        @php($cr = $totalBilled > 0 ? round(($totalPaid / $totalBilled) * 100, 1) : 0)
        <div class="text-2xl font-bold mt-1 text-blue-600">{{ $cr }}%</div>
        <div class="text-xs text-ink/45 mt-0.5">paid / billed</div>
    </div>
</div>

{{-- Revenue chart --}}
<div class="bg-white border border-cream-deep/60 rounded-2xl p-5 mb-4">
    <div class="flex items-baseline justify-between mb-3">
        <h2 class="text-base font-bold">Revenue 12 Bulan Terakhir</h2>
        <div class="text-xs text-ink/55">Tagihan vs Diterima</div>
    </div>
    <canvas id="revChart" height="80"></canvas>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
    {{-- Method breakdown --}}
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-5">
        <h2 class="text-base font-bold mb-3">Metode Pembayaran</h2>
        @if($methodBreakdown->isEmpty())
            <div class="text-sm text-ink/40 py-6 text-center">Belum ada pembayaran di rentang ini.</div>
        @else
            <table class="min-w-full text-sm">
                <thead><tr class="text-ink/55 text-xs uppercase tracking-wide border-b border-cream-deep/60"><th class="text-left py-2">Method</th><th class="text-right">Jumlah</th><th class="text-right">Total</th></tr></thead>
                <tbody>
                @foreach($methodBreakdown as $m)
                    <tr class="border-b border-cream-deep/40">
                        <td class="py-2 uppercase font-medium">{{ $m->method }}</td>
                        <td class="text-right text-xs">{{ $m->cnt }}×</td>
                        <td class="text-right font-semibold">Rp {{ number_format($m->total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Aging --}}
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-5">
        <h2 class="text-base font-bold mb-3">Outstanding Aging</h2>
        <div class="space-y-2">
            @foreach($agingLabels as $k => $lbl)
                @php($val = $aging[$k])
                @php($color = $agingColors[$k])
                @php($pct = round(($val / $agingTotal) * 100, 1))
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="font-medium">{{ $lbl }}</span>
                        <span class="text-ink/55">Rp {{ number_format($val, 0, ',', '.') }} ({{ $pct }}%)</span>
                    </div>
                    <div class="h-2 bg-cream-deep/60 rounded-full overflow-hidden">
                        <div class="h-full {{ $color }}" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Top customers --}}
<div class="bg-white border border-cream-deep/60 rounded-2xl overflow-hidden">
    <div class="px-5 py-3 border-b border-cream-deep/60">
        <h2 class="text-base font-bold">Top 10 Pelanggan (by revenue)</h2>
    </div>
    <table class="min-w-full text-sm">
        <thead class="text-ink/55 text-xs uppercase tracking-wide bg-cream-deep/30">
            <tr>
                <th class="text-left px-5 py-3">#</th>
                <th class="text-left px-5 py-3">Code</th>
                <th class="text-left px-5 py-3">Nama</th>
                <th class="text-right px-5 py-3">Invoice</th>
                <th class="text-right px-5 py-3">Total Bayar</th>
            </tr>
        </thead>
        <tbody>
        @forelse($topCustomers as $i => $c)
            <tr class="border-b border-cream-deep/40">
                <td class="px-5 py-3 text-ink/55">{{ $i + 1 }}</td>
                <td class="px-5 py-3 font-mono text-xs">{{ $c->customer_code }}</td>
                <td class="px-5 py-3 font-semibold">{{ $c->full_name }}</td>
                <td class="px-5 py-3 text-right text-xs">{{ $c->invoice_count }}</td>
                <td class="px-5 py-3 text-right font-semibold">Rp {{ number_format($c->paid_total, 0, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-12 text-center text-ink/40">Belum ada data revenue di rentang ini.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const seriesData = @json($monthSeries);
new Chart(document.getElementById('revChart'), {
    type: 'bar',
    data: {
        labels: seriesData.map(d => d.label),
        datasets: [
            { label: 'Tagihan',   data: seriesData.map(d => d.billed), backgroundColor: '#f5c542' },
            { label: 'Diterima',  data: seriesData.map(d => d.paid),   backgroundColor: '#1a1a1a' },
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: ctx => ctx.dataset.label + ': Rp ' + ctx.raw.toLocaleString('id-ID')
                }
            }
        },
        scales: {
            y: { ticks: { callback: v => 'Rp ' + (v/1e6).toFixed(0) + 'jt' } }
        }
    }
});
</script>
@endsection
