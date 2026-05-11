@extends('layouts.app')
@section('title','Dashboard')
@section('breadcrumb','Dashboard')

@section('content')
<div class="space-y-5">

    {{-- Hero greeting + main stats --}}
    <div class="bg-cream-card rounded-3xl p-5 sm:p-6 relative overflow-hidden shadow-card">
        <div class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-accent/30 blur-3xl"></div>
        <div class="absolute -bottom-24 right-32 w-64 h-64 rounded-full bg-accent-soft/50 blur-3xl"></div>

        <div class="relative grid grid-cols-1 lg:grid-cols-3 gap-6 items-center">
            <div class="lg:col-span-2">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink leading-tight">
                    Halo, {{ explode(' ', auth()->user()->name)[0] ?? 'Admin' }} 👋
                </h1>
                <p class="mt-1 text-ink/55 text-xs sm:text-sm">{{ now()->locale('id')->translatedFormat('l, d F Y') }} — selamat datang kembali.</p>

                <div class="mt-5 grid grid-cols-2 md:grid-cols-4 gap-2.5">
                    <a href="{{ route('customers.index') }}" class="bg-white rounded-2xl p-3 shadow-card hover:shadow-soft transition group">
                        <div class="w-9 h-9 rounded-xl bg-accent/30 text-ink flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 10-8 0 4 4 0 008 0zM2 22a10 10 0 0120 0"/></svg>
                        </div>
                        <div class="text-xl sm:text-2xl font-extrabold">{{ number_format($totalPelanggan, 0, ',', '.') }}</div>
                        <div class="text-xs text-ink/55 font-medium mt-0.5">Total Pelanggan</div>
                    </a>
                    <a href="{{ route('users.index') }}" class="bg-white rounded-2xl p-3 shadow-card hover:shadow-soft transition">
                        <div class="w-9 h-9 rounded-xl bg-accent/30 text-ink flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M21 12c0 4.97-4.03 9-9 9s-9-4.03-9-9 4.03-9 9-9 9 4.03 9 9z"/></svg>
                        </div>
                        <div class="text-xl sm:text-2xl font-extrabold">{{ number_format($totalLayanan, 0, ',', '.') }}</div>
                        <div class="text-xs text-ink/55 font-medium mt-0.5">Total Layanan</div>
                    </a>
                    <div class="bg-white rounded-2xl p-3 shadow-card">
                        <div class="w-9 h-9 rounded-xl bg-accent/30 text-ink flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <div class="text-xl sm:text-2xl font-extrabold">{{ number_format($pelangganBaru, 0, ',', '.') }}</div>
                        <div class="text-xs text-ink/55 font-medium mt-0.5">Pelanggan Baru</div>
                    </div>
                    <div class="bg-ink rounded-2xl p-3 text-white shadow-card">
                        <div class="w-9 h-9 rounded-xl bg-accent text-ink flex items-center justify-center mb-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zM12 15.75h.008v.008H12v-.008z"/></svg>
                        </div>
                        <div class="text-xl sm:text-2xl font-extrabold">{{ number_format($isolir, 0, ',', '.') }}</div>
                        <div class="text-xs text-white/60 font-medium mt-0.5">Layanan Isolir</div>
                    </div>
                </div>
            </div>

            {{-- Satisfactory gauge-ish ring --}}
            <div class="hidden lg:flex flex-col items-center justify-center">
                @php
                    $totalForRatio = max($totalPelanggan, 1);
                    $aktifRatio = ($statusBuckets['aktif'] ?? 0) / $totalForRatio * 100;
                    $aktifPct = (int) round(min(max($aktifRatio, 0), 100));
                @endphp
                <div class="relative w-44 h-44">
                    <svg viewBox="0 0 120 120" class="w-full h-full -rotate-90">
                        <circle cx="60" cy="60" r="50" stroke="#fde79a" stroke-width="14" fill="none"/>
                        <circle cx="60" cy="60" r="50" stroke="#f5c542" stroke-width="14" fill="none"
                            stroke-linecap="round"
                            stroke-dasharray="{{ 314 * $aktifPct / 100 }} 314"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <div class="text-3xl font-extrabold text-ink">{{ $aktifPct }}%</div>
                        <div class="text-[11px] text-ink/55 font-medium mt-1 text-center">Layanan Aktif</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        {{-- Pelanggan Baru Tahunan --}}
        <div class="bg-white rounded-3xl p-5 shadow-card">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                <div>
                    <div class="font-bold text-base">Pelanggan Baru</div>
                    <div class="text-[11px] text-ink/50">Per bulan tahun {{ $year }}</div>
                </div>
                <form method="GET" class="flex items-center gap-1.5">
                    <select name="year" class="bg-cream-deep/60 border-0 rounded-lg text-xs px-2 py-1.5 font-medium focus:ring-2 focus:ring-accent">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>
                        @endfor
                    </select>
                    <button class="px-2.5 py-1.5 text-xs bg-ink text-white rounded-lg font-semibold hover:bg-black transition">OK</button>
                </form>
            </div>
            <div class="relative h-48">
                <canvas id="chartYearly"></canvas>
            </div>
        </div>

        {{-- Pemasukan Per Bulan --}}
        <div class="bg-white rounded-3xl p-5 shadow-card">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <div class="font-bold text-base">Pemasukan Bulanan</div>
                    <div class="text-[11px] text-ink/50">Tahun {{ $year }}</div>
                </div>
                <span class="text-[11px] font-semibold text-ink/60 bg-cream-deep/60 px-2 py-1 rounded-lg">
                    Rp {{ number_format(array_sum($revenuePerMonth), 0, ',', '.') }}
                </span>
            </div>
            <div class="relative h-48">
                <canvas id="chartRevenue"></canvas>
            </div>
        </div>

        {{-- Komposisi Status --}}
        <div class="bg-white rounded-3xl p-5 shadow-card">
            <div class="font-bold text-base">Komposisi Status</div>
            <div class="text-[11px] text-ink/50 mb-3">Distribusi layanan</div>
            <div class="relative h-48">
                <canvas id="chartStatus"></canvas>
            </div>
        </div>
    </div>

    @if($billing)
        {{-- Billing summary --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="bg-white rounded-3xl p-5 shadow-card">
                <div class="text-xs text-ink/55 uppercase font-semibold">Total Outstanding</div>
                <div class="text-2xl font-bold mt-1">Rp {{ number_format($billing['outstanding'], 0, ',', '.') }}</div>
                <a href="{{ route('invoices.index') }}" class="text-xs text-ink/55 hover:text-ink mt-2 inline-block">Lihat tagihan →</a>
            </div>
            <div class="bg-white rounded-3xl p-5 shadow-card">
                <div class="text-xs text-ink/55 uppercase font-semibold">Belum Lunas</div>
                <div class="text-2xl font-bold mt-1">{{ $billing['belum_lunas_count'] }}</div>
            </div>
            <div class="bg-ink text-white rounded-3xl p-5 shadow-card">
                <div class="text-xs text-white/60 uppercase font-semibold">Terlambat</div>
                <div class="text-2xl font-bold mt-1 text-accent">{{ $billing['terlambat_count'] }}</div>
            </div>
            <div class="bg-white rounded-3xl p-5 shadow-card">
                <div class="text-xs text-ink/55 uppercase font-semibold">Lunas Bulan Ini</div>
                <div class="text-xl font-bold mt-1">Rp {{ number_format($billing['lunas_bulan_ini'], 0, ',', '.') }}</div>
            </div>
        </div>
    @endif

    {{-- Health + recent customers --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="bg-ink rounded-3xl p-6 text-white shadow-card relative overflow-hidden">
            <div class="absolute -top-10 -right-10 w-40 h-40 rounded-full bg-accent/20 blur-2xl"></div>
            <div class="font-bold text-lg relative">Kesehatan Layanan</div>
            <div class="text-xs text-white/55 mt-0.5 mb-4 relative">Ringkasan billing & pertumbuhan</div>
            <ul class="space-y-3 text-sm relative">
                <li class="flex items-center justify-between">
                    <span class="text-white/70">Layanan Aktif Billing</span>
                    <span class="bg-accent text-ink font-bold px-3 py-1 rounded-xl">{{ number_format($kesehatan['billing_active']) }}</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-white/70">Layanan Non-Aktif</span>
                    <span class="bg-white/10 text-white font-bold px-3 py-1 rounded-xl">{{ number_format($kesehatan['billing_non_active']) }}</span>
                </li>
                <li class="flex items-center justify-between">
                    <span class="text-white/70">Pelanggan Baru Bulan Lalu</span>
                    <span class="bg-white/10 text-white font-bold px-3 py-1 rounded-xl">{{ number_format($kesehatan['pelanggan_baru_lalu']) }}</span>
                </li>
            </ul>
        </div>

        <div class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-card">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div>
                    <div class="font-bold text-lg">Pelanggan Terbaru</div>
                    <div class="text-xs text-ink/50 mt-0.5">8 pelanggan terakhir terdaftar</div>
                </div>
                <a href="{{ route('customers.index') }}" class="text-xs font-semibold text-ink/60 hover:text-ink bg-cream-deep/60 px-3 py-1.5 rounded-xl">Lihat semua →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wider text-ink/45">
                            <th class="py-2 font-semibold">Nama</th>
                            <th class="py-2 font-semibold">No. Telp</th>
                            <th class="py-2 font-semibold">Dibuat</th>
                            <th class="py-2 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pelangganTerbaru as $row)
                            <tr class="border-t border-cream-deep/60">
                                <td class="py-3 font-medium">{{ $row->full_name }}</td>
                                <td class="py-3 text-ink/70">{{ $row->phone ?? '-' }}</td>
                                <td class="py-3 text-ink/70">{{ $row->created_at->format('d M Y H:i') }}</td>
                                <td class="py-3">
                                    <span class="px-2.5 py-1 rounded-xl text-xs font-semibold
                                        @class([
                                            'bg-emerald-100 text-emerald-800' => $row->status === 'active',
                                            'bg-amber-100 text-amber-800' => $row->status === 'pending',
                                            'bg-rose-100 text-rose-800' => in_array($row->status, ['inactive','isolir']),
                                            'bg-cream-deep text-ink/70' => $row->status === 'free',
                                        ])">{{ ucfirst($row->status) }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-8 text-center text-ink/40">Belum ada pelanggan.</td></tr>
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
    Chart.defaults.font.family = 'Plus Jakarta Sans, sans-serif';
    Chart.defaults.color = '#6b6b6b';

    const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];

    const yearly = document.getElementById('chartYearly').getContext('2d');
    new Chart(yearly, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Pelanggan Baru',
                data: @json($perMonth),
                backgroundColor: ctx => {
                    const c = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                    c.addColorStop(0, '#f5c542');
                    c.addColorStop(1, '#fde79a');
                    return c;
                },
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 18,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } }, grid: { color: 'rgba(0,0,0,.05)' } },
                x: { ticks: { font: { size: 10 } }, grid: { display: false } },
            },
            plugins: { legend: { display: false } }
        }
    });

    const fmtIDR = v => 'Rp ' + (v >= 1e6 ? (v/1e6).toFixed(1)+'jt' : v >= 1e3 ? Math.round(v/1e3)+'rb' : v);
    const revenue = document.getElementById('chartRevenue').getContext('2d');
    new Chart(revenue, {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Pemasukan',
                data: @json($revenuePerMonth),
                backgroundColor: ctx => {
                    const c = ctx.chart.ctx.createLinearGradient(0, 0, 0, 200);
                    c.addColorStop(0, '#1a1a1a');
                    c.addColorStop(1, '#3a3a3a');
                    return c;
                },
                borderRadius: 8,
                borderSkipped: false,
                maxBarThickness: 18,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { font: { size: 10 }, callback: fmtIDR }, grid: { color: 'rgba(0,0,0,.05)' } },
                x: { ticks: { font: { size: 10 } }, grid: { display: false } },
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (c) => 'Rp ' + Number(c.raw).toLocaleString('id-ID')
                    }
                }
            }
        }
    });

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
                backgroundColor: ['#f5c542','#1a1a1a','#fde79a','#cfcfcf'],
                borderWidth: 0,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 8, boxHeight: 8, usePointStyle: true, padding: 10, font: { size: 11, weight: 600 } }
                }
            },
            cutout: '68%'
        }
    });
});
</script>
@endpush
@endsection
