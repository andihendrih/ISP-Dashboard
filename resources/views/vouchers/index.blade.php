@extends('layouts.app')
@section('title','Voucher Hotspot')

@section('content')
<div class="flex items-start justify-between gap-3 mb-4">
    <div>
        <h1 class="text-2xl font-bold">Voucher Hotspot</h1>
        <p class="text-sm text-ink/55">User RADIUS dengan grup <code>Hotspot*</code> &amp; <code>HS_*</code>. Voucher tidak ikut billing bulanan.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('vouchers.generate-form') }}" class="px-4 py-2 bg-accent text-ink rounded-xl text-sm font-semibold">+ Generate Voucher</a>
        <form method="POST" action="{{ route('vouchers.bulk-expired') }}" onsubmit="return confirm('Hapus SEMUA voucher expired? Action ini hard delete dari RADIUS dan tidak bisa di-undo.');">
            @csrf
            <button class="px-4 py-2 bg-rose-600 text-white rounded-xl text-sm font-semibold hover:bg-rose-700">🗑 Hapus Expired</button>
        </form>
    </div>
</div>

@if(session('success')) <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-3 mb-3 text-sm">{{ session('success') }}</div> @endif
@if(session('error'))   <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-3 mb-3 text-sm">{{ session('error') }}</div> @endif

<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55">Total Voucher</div>
        <div class="text-3xl font-extrabold mt-1">{{ number_format($total) }}</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55">Aktif</div>
        <div class="text-3xl font-extrabold mt-1 text-emerald-700">{{ number_format($activeCount) }}</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55">Expired</div>
        <div class="text-3xl font-extrabold mt-1 text-rose-700">{{ number_format($expiredCount) }}</div>
    </div>
</div>

<form method="GET" class="bg-white border border-cream-deep/60 rounded-2xl p-3 mb-4 flex gap-2 items-end flex-wrap">
    <div>
        <label class="text-xs text-ink/55">Status</label>
        <select name="status" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
            <option value="all"     @selected($filterStatus==='all')>Semua</option>
            <option value="active"  @selected($filterStatus==='active')>Aktif</option>
            <option value="expired" @selected($filterStatus==='expired')>Expired</option>
        </select>
    </div>
    <div>
        <label class="text-xs text-ink/55">Grup</label>
        <select name="group" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
            <option value="">Semua grup</option>
            @foreach($groups as $g)
                <option value="{{ $g }}" @selected($filterGroup === $g)>{{ $g }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex-1 min-w-[200px]">
        <label class="text-xs text-ink/55">Cari kode</label>
        <input name="q" value="{{ $search }}" class="block w-full border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1" placeholder="kode voucher...">
    </div>
    <button class="px-4 py-2 bg-accent text-ink rounded-xl text-sm font-semibold">Filter</button>
    <a href="{{ route('vouchers.index') }}" class="px-3 py-2 text-sm text-ink/55 hover:bg-cream-deep/60 rounded-xl">Reset</a>
</form>

<div class="bg-white border border-cream-deep/60 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-ink/55 text-xs uppercase tracking-wide bg-cream-deep/30">
            <tr>
                <th class="text-left px-5 py-3">Kode</th>
                <th class="text-left px-5 py-3">Grup</th>
                <th class="text-left px-5 py-3">Expired</th>
                <th class="text-left px-5 py-3">Last Seen</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($enriched as $v)
            <tr class="border-b border-cream-deep/40">
                <td class="px-5 py-3 font-mono">{{ $v['username'] }}</td>
                <td class="px-5 py-3 text-xs">{{ $v['groupname'] }}</td>
                <td class="px-5 py-3 text-xs">{{ $v['expired']?->format('d M Y H:i') ?? '—' }}</td>
                <td class="px-5 py-3 text-xs text-ink/65">{{ $v['last_seen'] ? \Illuminate\Support\Carbon::parse($v['last_seen'])->diffForHumans() : '—' }}</td>
                <td class="px-5 py-3">
                    @if($v['is_expired'])
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold border bg-rose-50 text-rose-700 border-rose-200">Expired</span>
                    @else
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold border bg-emerald-50 text-emerald-700 border-emerald-200">Aktif</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <form method="POST" action="{{ route('vouchers.destroy', urlencode($v['username'])) }}" class="inline" onsubmit="return confirm('Hapus voucher {{ $v['username'] }}?');">
                        @csrf @method('DELETE')
                        <button class="text-rose-600 text-xs hover:text-rose-800">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-12 text-center text-ink/40">Tidak ada voucher yang cocok dengan filter.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $rows->links() }}</div>
@endsection
