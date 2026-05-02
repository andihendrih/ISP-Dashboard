@extends('layouts.app')
@section('title','Paket Layanan')
@section('breadcrumb','Billing / Paket Layanan')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Paket Layanan</h1>
        <p class="text-sm text-ink/55">Master harga per paket. Dipakai untuk auto-generate invoice bulanan.</p>
    </div>
    <a href="{{ route('plans.create') }}" class="px-4 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black">+ Tambah Paket</a>
</div>

<div class="bg-white rounded-3xl shadow-card overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-cream-deep/60 text-ink/70">
            <tr class="text-left">
                <th class="px-5 py-3">Kode</th>
                <th class="px-5 py-3">Nama</th>
                <th class="px-5 py-3">Tipe</th>
                <th class="px-5 py-3">Rate Limit</th>
                <th class="px-5 py-3">Group RADIUS</th>
                <th class="px-5 py-3 text-right">Harga / bulan</th>
                <th class="px-5 py-3 text-right">PPN</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($plans as $p)
            <tr class="border-t border-cream-deep/60 hover:bg-cream-card/40">
                <td class="px-5 py-3 font-mono text-xs text-ink/70">{{ $p->code }}</td>
                <td class="px-5 py-3 font-semibold">{{ $p->name }}</td>
                <td class="px-5 py-3 uppercase text-xs">{{ $p->service_type }}</td>
                <td class="px-5 py-3">{{ $p->rate_limit ?? '-' }}</td>
                <td class="px-5 py-3 font-mono text-xs">{{ $p->radius_group ?? '-' }}</td>
                <td class="px-5 py-3 text-right font-semibold">Rp {{ number_format($p->price, 0, ',', '.') }}</td>
                <td class="px-5 py-3 text-right">{{ rtrim(rtrim(number_format($p->tax_percent, 2, '.', ''), '0'), '.') }}%</td>
                <td class="px-5 py-3">
                    @if($p->is_active)
                        <span class="inline-block px-2 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-medium">Aktif</span>
                    @else
                        <span class="inline-block px-2 py-1 rounded-lg bg-ink/10 text-ink/60 text-xs">Non-aktif</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('plans.edit', $p) }}" class="text-ink/60 hover:text-ink mr-3">Edit</a>
                    <form method="POST" action="{{ route('plans.destroy', $p) }}" class="inline" onsubmit="return confirm('Hapus paket ini?')">
                        @csrf @method('DELETE')
                        <button class="text-rose-600 hover:text-rose-800">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="9" class="px-5 py-8 text-center text-ink/50">Belum ada paket. <a href="{{ route('plans.create') }}" class="text-ink underline">Buat paket pertama</a>.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-3">{{ $plans->links() }}</div>
@endsection
