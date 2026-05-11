@extends('layouts.app')
@section('title','Invoice — '.$invoice->invoice_number)
@section('breadcrumb','Tenant Billing / Invoices / '.$invoice->invoice_number)

@section('content')
@if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 space-y-4">
        <div class="bg-white rounded-3xl shadow-card p-5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="text-xs text-ink/55">Invoice</div>
                    <h1 class="font-mono font-bold text-xl">{{ $invoice->invoice_number }}</h1>
                </div>
                @php
                    $color = match($invoice->status) {
                        'paid' => 'bg-emerald-50 text-emerald-700',
                        'overdue' => 'bg-rose-50 text-rose-700',
                        'cancelled' => 'bg-ink/10 text-ink/60',
                        default => 'bg-amber-50 text-amber-700',
                    };
                @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $color }}">{{ $invoice->status }}</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4 text-sm">
                <div><div class="text-xs text-ink/55">Tenant</div><div class="font-semibold">{{ $invoice->tenant?->name ?? '—' }} <span class="text-xs text-ink/45 font-mono">({{ $invoice->tenant?->code }})</span></div></div>
                <div><div class="text-xs text-ink/55">Plan</div><div>{{ $invoice->plan?->name ?? '—' }}</div></div>
                <div><div class="text-xs text-ink/55">Periode</div><div>{{ $invoice->period_start?->format('d M Y') }} — {{ $invoice->period_end?->format('d M Y') }}</div></div>
                <div><div class="text-xs text-ink/55">Due Date</div><div>{{ $invoice->due_date?->format('d M Y') }}</div></div>
                <div><div class="text-xs text-ink/55">Prorate</div><div>{{ number_format($invoice->prorate_factor * 100, 2) }}%</div></div>
                <div><div class="text-xs text-ink/55">Paid At</div><div>{{ $invoice->paid_at?->format('d M Y H:i') ?? '—' }}</div></div>
            </div>
            <div class="mt-4 border-t border-cream-deep/60 pt-3 flex items-center justify-between">
                <div>
                    <div class="text-xs text-ink/55">Total</div>
                    <div class="text-2xl font-bold text-accent-strong">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</div>
                </div>
                <div class="text-right">
                    <div class="text-xs text-ink/55">Sisa</div>
                    <div class="text-xl font-semibold {{ $invoice->remainingAmount() > 0 ? 'text-rose-700' : 'text-emerald-700' }}">Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        {{-- Payments --}}
        <div class="bg-white rounded-3xl shadow-card p-5">
            <h2 class="font-bold text-sm mb-3">Pembayaran ({{ $invoice->payments->count() }})</h2>
            @if($invoice->payments->isEmpty())
                <p class="text-sm text-ink/45">Belum ada pembayaran.</p>
            @else
                <div class="space-y-3">
                @foreach($invoice->payments as $pay)
                    <div class="border border-cream-deep/60 rounded-xl p-3 flex items-start gap-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono font-semibold">Rp {{ number_format($pay->amount, 0, ',', '.') }}</span>
                                @php
                                    $pc = match($pay->status) {
                                        'confirmed' => 'bg-emerald-50 text-emerald-700',
                                        'rejected'  => 'bg-rose-50 text-rose-700',
                                        default     => 'bg-amber-50 text-amber-700',
                                    };
                                @endphp
                                <span class="text-xs px-2 py-0.5 rounded-full {{ $pc }}">{{ $pay->status }}</span>
                            </div>
                            <div class="text-xs text-ink/55 mt-1">
                                {{ $pay->method }} {{ $pay->reference ? '· '.$pay->reference : '' }} · {{ $pay->transferred_at?->format('d M Y') }}
                                @if($pay->confirmer) · oleh {{ $pay->confirmer->name }} @endif
                            </div>
                            @if($pay->note)<div class="text-xs text-ink/60 mt-1">{{ $pay->note }}</div>@endif
                            @if($pay->proof_path)
                                <a href="{{ asset('storage/'.$pay->proof_path) }}" target="_blank" class="text-xs text-sky-700 hover:underline">📎 Lihat bukti</a>
                            @endif
                        </div>
                        @if($pay->status === 'pending')
                        <div class="flex flex-col gap-1">
                            <form method="POST" action="{{ route('tenant_billing.payments.confirm', $pay) }}">
                                @csrf
                                <button class="text-xs px-3 py-1 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">Konfirmasi</button>
                            </form>
                            <form method="POST" action="{{ route('tenant_billing.payments.reject', $pay) }}" onsubmit="this.querySelector('input[name=note]').value=prompt('Alasan reject?')||''">
                                @csrf
                                <input type="hidden" name="note">
                                <button class="text-xs px-3 py-1 bg-rose-50 text-rose-700 border border-rose-200 rounded-lg hover:bg-rose-100">Tolak</button>
                            </form>
                        </div>
                        @endif
                    </div>
                @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        <div class="bg-white rounded-3xl shadow-card p-5">
            <h2 class="font-bold text-sm mb-3">Aksi Cepat</h2>
            <a href="{{ route('tenant_billing.invoices.pdf', $invoice) }}" target="_blank" class="block text-center w-full px-4 py-2 bg-ink text-white rounded-xl text-sm hover:bg-black mb-2">📄 Cetak PDF</a>
            @if(!in_array($invoice->status, ['paid','cancelled']))
                <form method="POST" action="{{ route('tenant_billing.invoices.mark-paid', $invoice) }}" onsubmit="return confirm('Tandai invoice lunas?')" class="mb-2">
                    @csrf
                    <button class="w-full px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm hover:bg-emerald-700">Tandai Lunas (Manual)</button>
                </form>
                <form method="POST" action="{{ route('tenant_billing.invoices.cancel', $invoice) }}" onsubmit="return confirm('Batalkan invoice?')">
                    @csrf
                    <button class="w-full px-4 py-2 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl text-sm hover:bg-rose-100">Batalkan Invoice</button>
                </form>
            @endif
        </div>

        @if(!in_array($invoice->status, ['paid','cancelled']))
        <div class="bg-white rounded-3xl shadow-card p-5">
            <h2 class="font-bold text-sm mb-3">Catat Pembayaran Manual</h2>
            <form method="POST" action="{{ route('tenant_billing.payments.store', $invoice) }}" enctype="multipart/form-data" class="space-y-2">
                @csrf
                <input type="number" name="amount" min="1" value="{{ $invoice->remainingAmount() }}" required class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm" placeholder="Amount">
                <select name="method" required class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="va">VA</option>
                    <option value="ewallet">E-Wallet</option>
                    <option value="cash">Cash</option>
                    <option value="other">Lainnya</option>
                </select>
                <input type="text" name="reference" placeholder="No. ref / VA" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                <input type="date" name="transferred_at" value="{{ now()->toDateString() }}" required class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                <input type="file" name="proof" accept="image/*,application/pdf" class="w-full text-xs">
                <textarea name="note" rows="2" placeholder="Catatan" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm"></textarea>
                <button class="w-full px-4 py-2 bg-ink text-white rounded-xl text-sm hover:bg-black">Simpan Pembayaran</button>
            </form>
        </div>
        @endif
    </div>
</div>
@endsection
