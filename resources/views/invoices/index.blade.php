@extends('layouts.app')
@section('title','Tagihan')
@section('breadcrumb','Billing / Tagihan')

@section('content')
@php
    $statusBadges = [
        'belum_lunas' => ['Belum Lunas', 'bg-amber-100 text-amber-900'],
        'lunas'       => ['Lunas',       'bg-emerald-100 text-emerald-900'],
        'terlambat'   => ['Terlambat',   'bg-rose-100 text-rose-900'],
        'cancelled'   => ['Cancelled',   'bg-ink/10 text-ink/60'],
    ];
@endphp

<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Tagihan</h1>
        <p class="text-sm text-ink/55">Kelola invoice bulanan pelanggan.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('invoices.create') }}" class="px-4 py-2 bg-white border border-ink/10 rounded-xl shadow-card hover:bg-cream-card">+ Buat Manual</a>
        <button onclick="document.getElementById('genModal').showModal()" class="px-4 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black">⚡ Generate Bulanan</button>
    </div>
</div>

{{-- Stat tiles --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-3xl p-5 shadow-card">
        <div class="text-xs text-ink/55 uppercase font-semibold">Outstanding</div>
        <div class="text-2xl font-bold mt-1">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-3xl p-5 shadow-card">
        <div class="text-xs text-ink/55 uppercase font-semibold">Belum Lunas</div>
        <div class="text-2xl font-bold mt-1">{{ $summary['count_belum_lunas'] }}</div>
    </div>
    <div class="bg-ink text-white rounded-3xl p-5 shadow-card">
        <div class="text-xs text-white/60 uppercase font-semibold">Terlambat</div>
        <div class="text-2xl font-bold mt-1 text-accent">{{ $summary['count_terlambat'] }}</div>
    </div>
    <div class="bg-white rounded-3xl p-5 shadow-card">
        <div class="text-xs text-ink/55 uppercase font-semibold">Lunas Bulan Ini</div>
        <div class="text-2xl font-bold mt-1">{{ $summary['count_lunas_bulan'] }}</div>
    </div>
</div>

{{-- Filter bar --}}
<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input name="q" value="{{ $q }}" placeholder="Cari no.invoice / nama / kode pelanggan…" class="flex-1 min-w-[240px] border border-ink/10 rounded-xl px-3 py-2 bg-white">
    <select name="status" class="border border-ink/10 rounded-xl px-3 py-2 bg-white">
        <option value="">Semua status</option>
        @foreach($statusBadges as $k => [$lbl, $cls])
            <option value="{{ $k }}" @selected($status === $k)>{{ $lbl }}</option>
        @endforeach
    </select>
    <input name="period" value="{{ $period }}" placeholder="YYYY-MM" pattern="\d{4}-\d{2}" class="border border-ink/10 rounded-xl px-3 py-2 bg-white w-32">
    <button class="px-4 py-2 bg-ink text-white rounded-xl">Filter</button>
    @if($q || $status || $period)
        <a href="{{ route('invoices.index') }}" class="px-4 py-2 rounded-xl text-ink/60 hover:bg-cream-deep/60">Reset</a>
    @endif
</form>

<div class="bg-white rounded-3xl shadow-card overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-cream-deep/60 text-ink/70">
            <tr class="text-left">
                <th class="px-5 py-3">No. Invoice</th>
                <th class="px-5 py-3">Pelanggan</th>
                <th class="px-5 py-3">Periode</th>
                <th class="px-5 py-3">Jatuh Tempo</th>
                <th class="px-5 py-3 text-right">Total</th>
                <th class="px-5 py-3 text-right">Sisa</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($invoices as $inv)
            <tr class="border-t border-cream-deep/60 hover:bg-cream-card/40">
                <td class="px-5 py-3 font-mono text-xs">{{ $inv->invoice_number }}</td>
                <td class="px-5 py-3">
                    <div class="font-semibold">{{ $inv->customer?->full_name ?? '-' }}</div>
                    <div class="text-xs text-ink/50 font-mono">{{ $inv->customer?->customer_code }}</div>
                </td>
                <td class="px-5 py-3">
                    {{ \Carbon\Carbon::create($inv->period_year, $inv->period_month, 1)->format('M Y') }}
                    @if($inv->is_prorated)<span class="ml-1 text-[10px] uppercase bg-accent-soft text-ink px-1.5 py-0.5 rounded">prorate</span>@endif
                </td>
                <td class="px-5 py-3">{{ optional($inv->due_date)->format('d M Y') }}</td>
                <td class="px-5 py-3 text-right font-semibold">Rp {{ number_format($inv->total_amount, 0, ',', '.') }}</td>
                <td class="px-5 py-3 text-right">Rp {{ number_format($inv->outstanding, 0, ',', '.') }}</td>
                <td class="px-5 py-3">
                    @php([$lbl, $cls] = $statusBadges[$inv->status] ?? [$inv->status, 'bg-ink/10'])
                    <span class="inline-block px-2 py-1 rounded-lg text-xs font-medium {{ $cls }}">{{ $lbl }}</span>
                </td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('invoices.pdf', $inv) }}" target="_blank" class="text-ink/55 hover:text-ink mr-2" title="Cetak PDF">📄</a>
                    <a href="{{ route('invoices.show', $inv) }}" class="text-ink/60 hover:text-ink">Detail →</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="px-5 py-8 text-center text-ink/50">Tidak ada invoice.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>

{{-- Generate Modal --}}
<dialog id="genModal" class="rounded-3xl p-0 backdrop:bg-ink/40">
    <form method="POST" action="{{ route('invoices.generate-batch') }}" class="bg-white rounded-3xl p-6 w-[420px]">
        @csrf
        <h2 class="text-lg font-bold mb-1">Generate Invoice Bulanan</h2>
        <p class="text-sm text-ink/55 mb-4">Akan dibuat invoice untuk semua pelanggan aktif yang punya paket layanan & billing aktif. Pelanggan baru auto-prorate.</p>
        <div class="space-y-3">
            <div>
                <label class="text-sm font-medium">Periode</label>
                <input name="period" type="text" value="{{ now()->format('Y-m') }}" pattern="\d{4}-\d{2}" required class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">Hari Jatuh Tempo (default: {{ config('ahnet.billing.due_day') }})</label>
                <input name="due_day" type="number" min="1" max="31" value="{{ config('ahnet.billing.due_day') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            </div>
        </div>
        <div class="mt-5 flex gap-2 justify-end">
            <button type="button" onclick="document.getElementById('genModal').close()" class="px-4 py-2 rounded-xl text-ink/60">Batal</button>
            <button class="px-4 py-2 bg-ink text-white rounded-xl hover:bg-black">Generate</button>
        </div>
    </form>
</dialog>
@endsection
