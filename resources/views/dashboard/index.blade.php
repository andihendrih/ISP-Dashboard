@extends('layouts.app')
@section('title','Dashboard')

@section('content')
<div class="space-y-6">

    {{-- Top stat cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-100">
            <div class="text-sm text-slate-500">Total Pelanggan</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($totalPelanggan, 0, ',', '.') }}</div>
            <a href="{{ route('customers.index') }}" class="text-xs text-emerald-600 mt-2 inline-block">Klik untuk lihat tipe pelanggan</a>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-100">
            <div class="text-sm text-slate-500">Total Layanan</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($totalLayanan, 0, ',', '.') }}</div>
            <a href="{{ route('users.index') }}" class="text-xs text-emerald-600 mt-2 inline-block">Klik untuk lihat status layanan</a>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-100">
            <div class="text-sm text-slate-500">Pelanggan Baru Bulan Ini</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($pelangganBaru, 0, ',', '.') }}</div>
            <span class="text-xs text-emerald-600 mt-2 inline-block">Klik untuk buka laporan pelanggan/layanan baru</span>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-100">
            <div class="text-sm text-slate-500">Layanan Isolir</div>
            <div class="text-3xl font-bold mt-1">{{ number_format($isolir, 0, ',', '.') }}</div>
            <div class="text-xs text-slate-400 mt-2">Online aktif: {{ $online ?? '—' }}</div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5 border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="font-semibold">Pelanggan Baru Tahunan</div>
                <form method="GET" class="flex items-center gap-2">
                    <select name="year" class="border-slate-300 rounded-lg text-sm">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                        @endfor
                    </select>
                    <button class="px-3 py-1.5 text-sm bg-emerald-500 text-white rounded-lg">Terapkan</button>
                </form>
            </div>
            <canvas id="chartYearly" height="100"></canvas>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-100">
            <div class="font-semibold mb-4">Komposisi Status Layanan</div>
            <canvas id="chartStatus" height="220"></canvas>
        </div>
    </div>

    {{-- Health + recent customers --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-5 border border-slate-100">
            <div class="font-semibold mb-3">Kesehatan Layanan</div>
            <ul class="space-y-2 text-sm">
                <li class="flex justify-between"><span>Layanan Aktif Billing</span><span class="text-emerald-600 font-semibold">{{ number_format($kesehatan['billing_active']) }}</span></li>
                <li class="flex justify-between"><span>Layanan Non-Aktif Billing</span><span class="text-rose-600 font-semibold">{{ number_format($kesehatan['billing_non_active']) }}</span></li>
                <li class="flex justify-between"><span>Pelanggan Baru Bulan Lalu</span><span class="font-semibold">{{ number_format($kesehatan['pelanggan_baru_lalu']) }}</span></li>
            </ul>
        </div>
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-5 border border-slate-100">
            <div class="font-semibold mb-3">Pelanggan Terbaru</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-slate-500">
                        <tr class="border-b border-slate-200">
                            <th class="text-left py-2">Nama</th>
                            <th class="text-left py-2">No. Telp</th>
                            <th class="text-left py-2">Dibuat</th>
                            <th class="text-left py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pelangganTerbaru as $row)
                            <tr class="border-b border-slate-100">
                                <td class="py-2">{{ $row->full_name }}</td>
                                <td class="py-2">{{ $row->phone ?? '-' }}</td>
                                <td class="py-2">{{ $row->created_at->format('d M Y H:i') }}</td>
                                <td class="py-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs
                                        @class([
                                            'bg-emerald-100 text-emerald-700' => $row->status === 'active',
                                            'bg-amber-100 text-amber-700' => $row->status === 'pending',
                                            'bg-rose-100 text-rose-700' => in_array($row->status, ['inactive','isolir']),
                                            'bg-slate-100 text-slate-700' => $row->status === 'free',
                                        ])
                                    ">{{ ucfirst($row->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-4 text-center text-slate-400">Belum ada pelanggan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') return;

    // Bar
    const yearly = document.getElementById('chartYearly').getContext('2d');
    new Chart(yearly, {
        type: 'bar',
        data: {
            labels: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
            datasets: [{
                label: 'Pelanggan Baru',
                data: @json($perMonth),
                backgroundColor: '#3b82f6',
                borderRadius: 6,
            }]
        },
        options: {
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } }
        }
    });

    // Donut
    const status = document.getElementById('chartStatus').getContext('2d');
    new Chart(status, {
        type: 'doughnut',
        data: {
            labels: ['Aktif','Free','Menunggu','Non-Aktif'],
            datasets: [{
                data: [
                    {{ $statusBuckets['aktif'] }},
                    {{ $statusBuckets['free'] }},
                    {{ $statusBuckets['menunggu'] }},
                    {{ $statusBuckets['non_aktif'] }},
                ],
                backgroundColor: ['#3b82f6','#10b981','#f59e0b','#ef4444'],
                borderWidth: 0
            }]
        },
        options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
    });
});
</script>
@endpush
@endsection
