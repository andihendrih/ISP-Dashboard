@extends('layouts.app')
@section('title','Invoice ' . $invoice->invoice_number)
@section('breadcrumb','Billing / Tagihan / ' . $invoice->invoice_number)

@section('content')
@php
    $badges = [
        'belum_lunas' => ['Belum Lunas', 'bg-amber-100 text-amber-900'],
        'lunas'       => ['Lunas',       'bg-emerald-100 text-emerald-900'],
        'terlambat'   => ['Terlambat',   'bg-rose-100 text-rose-900'],
        'cancelled'   => ['Cancelled',   'bg-ink/10 text-ink/60'],
    ];
    [$lbl, $cls] = $badges[$invoice->status] ?? [$invoice->status, 'bg-ink/10'];
@endphp

<div class="flex items-start justify-between mb-4 gap-4">
    <div>
        <div class="text-sm text-ink/55">Invoice</div>
        <h1 class="text-3xl font-extrabold font-mono">{{ $invoice->invoice_number }}</h1>
        <span class="inline-block mt-1 px-2 py-1 rounded-lg text-xs font-medium {{ $cls }}">{{ $lbl }}</span>
        @if($invoice->is_prorated)<span class="ml-1 text-[10px] uppercase bg-accent-soft text-ink px-2 py-1 rounded">PRORATE</span>@endif
    </div>
    <div class="flex gap-2 flex-wrap">
        <a href="{{ route('invoices.index') }}" class="px-4 py-2 bg-white border border-ink/10 rounded-xl">← Kembali</a>
        <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank" class="px-4 py-2 bg-white border border-ink/10 rounded-xl hover:bg-cream-deep/60">📄 Cetak Invoice</a>
        <a href="{{ route('invoices.pdf', $invoice) }}?download=1" class="px-4 py-2 bg-white border border-ink/10 rounded-xl hover:bg-cream-deep/60">⬇ Download Invoice</a>
        <a href="{{ route('invoices.receipt', $invoice) }}" target="_blank" class="px-4 py-2 bg-accent text-ink rounded-xl hover:brightness-95 font-semibold">🧾 Cetak Struk</a>
        @if(in_array($invoice->status, ['belum_lunas','terlambat']))
            <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" class="inline" onsubmit="return confirm('Batalkan invoice ini?')">
                @csrf
                <input type="hidden" name="reason" value="Dibatalkan oleh admin">
                <button class="px-4 py-2 bg-rose-100 text-rose-800 rounded-xl hover:bg-rose-200">Batalkan</button>
            </form>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    {{-- Left: details --}}
    <div class="md:col-span-2 space-y-4">
        <div class="bg-white rounded-3xl shadow-card p-6">
            <h3 class="font-bold mb-3">Detail Pelanggan</h3>
            <div class="grid grid-cols-2 gap-y-2 text-sm">
                <div class="text-ink/55">Nama</div>
                <div class="font-semibold">{{ $invoice->customer?->full_name }}</div>
                <div class="text-ink/55">Kode</div>
                <div class="font-mono">{{ $invoice->customer?->customer_code }}</div>
                <div class="text-ink/55">Layanan</div>
                <div class="uppercase">{{ $invoice->customer?->service_type }}</div>
                <div class="text-ink/55">Paket</div>
                <div>{{ $invoice->servicePlan?->name ?? '—' }}</div>
                <div class="text-ink/55">Telp</div>
                <div>{{ $invoice->customer?->phone ?? '—' }}</div>
                <div class="text-ink/55">Alamat</div>
                <div>{{ $invoice->customer?->address ?? '—' }}</div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-card p-6">
            <h3 class="font-bold mb-3">Rincian Tagihan</h3>
            <div class="grid grid-cols-2 gap-y-2 text-sm">
                <div class="text-ink/55">Periode</div>
                <div>{{ \Carbon\Carbon::create($invoice->period_year, $invoice->period_month, 1)->format('F Y') }}
                    <span class="text-ink/40 text-xs">({{ $invoice->period_start->format('d M') }} – {{ $invoice->period_end->format('d M Y') }})</span>
                </div>
                <div class="text-ink/55">Hari ditagih</div>
                <div>{{ $invoice->days_charged }} / {{ $invoice->days_in_month }} hari</div>
                <div class="text-ink/55">Subtotal</div>
                <div>Rp {{ number_format($invoice->base_amount, 0, ',', '.') }}</div>
                <div class="text-ink/55">PPN</div>
                <div>Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</div>
                <div class="text-ink/55 font-semibold">Total</div>
                <div class="font-bold text-lg">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</div>
                <div class="text-ink/55">Sudah Dibayar</div>
                <div class="text-emerald-700 font-semibold">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</div>
                <div class="text-ink/55">Sisa</div>
                <div class="text-rose-700 font-bold">Rp {{ number_format($invoice->outstanding, 0, ',', '.') }}</div>
                <div class="text-ink/55">Jatuh Tempo</div>
                <div>{{ optional($invoice->due_date)->format('d M Y') }}</div>
                @if($invoice->cancelled_at)
                    <div class="text-ink/55">Dibatalkan</div>
                    <div>{{ $invoice->cancelled_at->format('d M Y H:i') }} — {{ $invoice->cancelled_reason }}</div>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-card p-6">
            <h3 class="font-bold mb-3">Riwayat Pembayaran</h3>
            @if($invoice->payments->isEmpty())
                <p class="text-sm text-ink/50">Belum ada pembayaran tercatat.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="text-ink/70 text-left text-xs uppercase">
                        <tr><th class="py-2">Tgl</th><th>Metode</th><th>Referensi</th><th class="text-right">Jumlah</th><th>Pencatat</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach($invoice->payments->sortByDesc('paid_at') as $pay)
                        <tr class="border-t border-cream-deep/60">
                            <td class="py-2">{{ $pay->paid_at->format('d M Y H:i') }}</td>
                            <td class="uppercase text-xs">{{ $pay->method }}</td>
                            <td class="font-mono text-xs">{{ $pay->reference ?? '—' }}</td>
                            <td class="text-right font-semibold">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                            <td class="text-xs text-ink/55">{{ $pay->recorder?->name ?? '—' }}</td>
                            <td class="text-right">
                                <form method="POST" action="{{ route('payments.destroy', $pay) }}" onsubmit="return confirm('Hapus pembayaran ini?')">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 text-xs hover:underline">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Right: payment form --}}
    <div>
        @if($invoice->status === \App\Models\Invoice::STATUS_LUNAS)
            <div class="bg-emerald-50 border border-emerald-200 rounded-3xl p-6 text-center">
                <div class="text-emerald-800 font-bold text-lg">Sudah Lunas</div>
                <div class="text-emerald-700 text-sm mt-1">{{ $invoice->paid_at?->format('d M Y H:i') }}</div>
            </div>
        @elseif($invoice->status === \App\Models\Invoice::STATUS_CANCELLED)
            <div class="bg-ink/5 border border-ink/10 rounded-3xl p-6 text-center text-ink/60">Invoice sudah dibatalkan.</div>
        @else
            <form method="POST" action="{{ route('payments.store', $invoice) }}" class="bg-ink text-white rounded-3xl shadow-card p-6 space-y-3">
                @csrf
                <h3 class="font-bold text-accent">Catat Pembayaran</h3>
                <div>
                    <label class="text-xs text-white/70 uppercase">Jumlah (Rp)</label>
                    <input name="amount" type="number" step="0.01" min="0.01" value="{{ old('amount', $invoice->outstanding) }}" required
                           class="w-full mt-1 px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-white">
                </div>
                <div>
                    <label class="text-xs text-white/70 uppercase">Metode</label>
                    <select name="method" required class="w-full mt-1 px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-white">
                        <option value="cash" class="text-ink">Cash</option>
                        <option value="transfer" class="text-ink">Transfer Bank</option>
                        <option value="qris" class="text-ink">QRIS</option>
                        <option value="va" class="text-ink">Virtual Account</option>
                        <option value="other" class="text-ink">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs text-white/70 uppercase">Referensi (opsional)</label>
                    <input name="reference" placeholder="No. transaksi / berita acara" class="w-full mt-1 px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-white placeholder-white/30">
                </div>
                <div>
                    <label class="text-xs text-white/70 uppercase">Tanggal Bayar</label>
                    <input name="paid_at" type="datetime-local" value="{{ now()->format('Y-m-d\TH:i') }}" class="w-full mt-1 px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-white">
                </div>
                <div>
                    <label class="text-xs text-white/70 uppercase">Catatan</label>
                    <textarea name="notes" rows="2" class="w-full mt-1 px-3 py-2 rounded-lg bg-white/10 border border-white/15 text-white"></textarea>
                </div>
                <button class="w-full mt-2 px-4 py-2.5 bg-accent text-ink rounded-xl font-bold hover:bg-accent-soft">Tandai Bayar</button>
            </form>
        @endif
    </div>
</div>
@endsection
