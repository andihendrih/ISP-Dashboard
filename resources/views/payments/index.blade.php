@extends('layouts.app')
@section('title','Pembayaran')
@section('breadcrumb','Billing / Pembayaran')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Pembayaran</h1>
        <p class="text-sm text-ink/55">Riwayat semua transaksi pembayaran invoice.</p>
    </div>
</div>

<div class="bg-white rounded-3xl shadow-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-deep/60 text-ink/70">
            <tr class="text-left">
                <th class="px-5 py-3">Tgl Bayar</th>
                <th class="px-5 py-3">Invoice</th>
                <th class="px-5 py-3">Pelanggan</th>
                <th class="px-5 py-3">Metode</th>
                <th class="px-5 py-3">Referensi</th>
                <th class="px-5 py-3 text-right">Jumlah</th>
                <th class="px-5 py-3">Pencatat</th>
            </tr>
        </thead>
        <tbody>
        @forelse($payments as $p)
            <tr class="border-t border-cream-deep/60 hover:bg-cream-card/40">
                <td class="px-5 py-3">{{ $p->paid_at?->format('d M Y H:i') }}</td>
                <td class="px-5 py-3 font-mono text-xs">
                    <a href="{{ route('invoices.show', $p->invoice_id) }}" class="text-ink hover:underline">{{ $p->invoice?->invoice_number ?? '#'.$p->invoice_id }}</a>
                </td>
                <td class="px-5 py-3">{{ $p->invoice?->customer?->full_name ?? '—' }}</td>
                <td class="px-5 py-3 uppercase text-xs">{{ $p->method }}</td>
                <td class="px-5 py-3 font-mono text-xs">{{ $p->reference ?? '—' }}</td>
                <td class="px-5 py-3 text-right font-semibold">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                <td class="px-5 py-3 text-xs text-ink/55">{{ $p->recorder?->name ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-8 text-center text-ink/50">Belum ada pembayaran tercatat.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@endsection
