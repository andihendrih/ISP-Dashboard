@extends('layouts.app')
@section('title','Laporan Churn')

@section('content')
<div class="mb-4">
    <h1 class="text-xl sm:text-2xl font-bold">Laporan Churn &amp; Pertumbuhan</h1>
    <p class="text-sm text-ink/55">Pertumbuhan, churn rate, &amp; komposisi pelanggan AHNet.</p>
</div>

<form method="GET" class="bg-white border border-cream-deep/60 rounded-2xl p-3 mb-4 flex gap-2 items-end">
    <div>
        <label class="text-xs text-ink/55">Rentang (bulan)</label>
        <select name="months" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
            @foreach([3, 6, 12, 18, 24] as $m)
                <option value="{{ $m }}" @selected($months == $m)>{{ $m }} bulan</option>
            @endforeach
        </select>
    </div>
    <button class="px-4 py-2 bg-accent text-ink rounded-xl text-sm font-semibold">Update</button>
</form>

@php
    $totalNow      = array_sum($statusComposition ?: [0]);
    $totalNew      = collect($series)->sum('new');
    $totalLost     = collect($series)->sum('lost');
    $totalNet      = $totalNew - $totalLost;
    $avgChurn      = $totalNew > 0 ? round((collect($series)->avg('churn_pct') ?? 0), 2) : 0;
@endphp

<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mb-4">
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55 uppercase tracking-wide">Pelanggan Aktif</div>
        <div class="text-2xl font-bold mt-1">{{ ($statusComposition['active'] ?? 0) + ($statusComposition['free'] ?? 0) + ($statusComposition['isolir'] ?? 0) }}</div>
        <div class="text-xs text-ink/45">total profile: {{ $totalNow }}</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55 uppercase tracking-wide">Pelanggan Baru ({{ $months }}m)</div>
        <div class="text-2xl font-bold mt-1 text-emerald-600">+{{ $totalNew }}</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55 uppercase tracking-wide">Churned ({{ $months }}m)</div>
        <div class="text-2xl font-bold mt-1 text-red-600">−{{ $totalLost }}</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55 uppercase tracking-wide">Avg Churn Rate</div>
        <div class="text-2xl font-bold mt-1 {{ $avgChurn > 5 ? 'text-red-600' : 'text-amber-600' }}">{{ $avgChurn }}%</div>
        <div class="text-xs text-ink/45">per bulan</div>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
    <div class="col-span-2 bg-white border border-cream-deep/60 rounded-2xl p-5">
        <h2 class="text-base font-bold mb-3">Pertumbuhan Pelanggan</h2>
        <canvas id="growthChart" height="80"></canvas>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-5">
        <h2 class="text-base font-bold mb-3">Komposisi Status</h2>
        <canvas id="statusChart" height="180"></canvas>
        <div class="mt-3 space-y-1 text-xs">
            @foreach($statusComposition as $s => $c)
                <div class="flex justify-between"><span class="capitalize">{{ $s }}</span><span class="font-semibold">{{ $c }}</span></div>
            @endforeach
        </div>
    </div>
</div>

<div class="bg-white border border-cream-deep/60 rounded-2xl overflow-hidden mb-4">
    <div class="px-5 py-3 border-b border-cream-deep/60"><h2 class="text-base font-bold">Detail per Bulan</h2></div>
    <table class="min-w-full text-sm">
        <thead class="text-ink/55 text-xs uppercase tracking-wide bg-cream-deep/30">
            <tr>
                <th class="text-left px-5 py-3">Bulan</th>
                <th class="text-right px-5 py-3">Baru</th>
                <th class="text-right px-5 py-3">Hilang</th>
                <th class="text-right px-5 py-3">Net</th>
                <th class="text-right px-5 py-3">Aktif (akhir bln)</th>
                <th class="text-right px-5 py-3">Churn %</th>
            </tr>
        </thead>
        <tbody>
        @foreach($series as $row)
            <tr class="border-b border-cream-deep/40">
                <td class="px-5 py-3">{{ $row['label'] }}</td>
                <td class="px-5 py-3 text-right text-emerald-600">+{{ $row['new'] }}</td>
                <td class="px-5 py-3 text-right text-red-600">−{{ $row['lost'] }}</td>
                <td class="px-5 py-3 text-right font-semibold {{ $row['net'] < 0 ? 'text-red-600' : 'text-ink' }}">{{ $row['net'] >= 0 ? '+' : '' }}{{ $row['net'] }}</td>
                <td class="px-5 py-3 text-right">{{ $row['active_eom'] }}</td>
                <td class="px-5 py-3 text-right {{ $row['churn_pct'] > 5 ? 'text-red-600' : 'text-ink/65' }}">{{ $row['churn_pct'] }}%</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="bg-white border border-cream-deep/60 rounded-2xl overflow-hidden">
    <div class="px-5 py-3 border-b border-cream-deep/60"><h2 class="text-base font-bold">Pelanggan Churn Bulan Ini</h2></div>
    <table class="min-w-full text-sm">
        <thead class="text-ink/55 text-xs uppercase tracking-wide bg-cream-deep/30">
            <tr>
                <th class="text-left px-5 py-3">Code</th>
                <th class="text-left px-5 py-3">Nama</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-left px-5 py-3">Joined</th>
                <th class="text-left px-5 py-3">Expired</th>
            </tr>
        </thead>
        <tbody>
        @forelse($churnedThisMonth as $c)
            <tr class="border-b border-cream-deep/40">
                <td class="px-5 py-3 font-mono text-xs">{{ $c->customer_code }}</td>
                <td class="px-5 py-3 font-semibold">{{ $c->full_name }}</td>
                <td class="px-5 py-3 text-xs"><span class="inline-block px-2 py-0.5 rounded-full bg-red-50 text-red-700 border border-red-200">{{ ucfirst($c->status) }}</span></td>
                <td class="px-5 py-3 text-xs">{{ optional($c->joined_at)->format('d M Y') ?? '—' }}</td>
                <td class="px-5 py-3 text-xs">{{ optional($c->expired_at)->format('d M Y') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-12 text-center text-ink/40">Tidak ada churn bulan ini. 🎉</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ser = @json($series);
const stat = @json($statusComposition);

new Chart(document.getElementById('growthChart'), {
    type: 'bar',
    data: {
        labels: ser.map(d => d.label),
        datasets: [
            { label: 'Baru',  data: ser.map(d => d.new),     backgroundColor: '#10b981' },
            { label: 'Hilang', data: ser.map(d => -d.lost),  backgroundColor: '#ef4444' },
            { label: 'Net',    data: ser.map(d => d.net),    type: 'line', borderColor: '#1a1a1a', backgroundColor: '#f5c542', tension: .25, pointBackgroundColor: '#f5c542' },
        ]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(stat),
        datasets: [{
            data: Object.values(stat),
            backgroundColor: ['#10b981','#3b82f6','#f5c542','#ef4444','#9ca3af']
        }]
    },
    options: { plugins: { legend: { display: false } }, cutout: '65%' }
});
</script>
@endsection
