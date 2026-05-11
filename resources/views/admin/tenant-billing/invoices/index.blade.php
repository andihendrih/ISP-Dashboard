@extends('layouts.app')
@section('title','Tenant Billing — Invoices')
@section('breadcrumb','Tenant Billing / Invoices')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Invoices</h1>
        <p class="text-sm text-ink/55">Tagihan subscription tenant.</p>
    </div>
</div>

@if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>@endif

<form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
    <div>
        <label class="block text-xs text-ink/55 mb-1">Status</label>
        <select name="status" class="border border-ink/10 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">Semua</option>
            @foreach(['unpaid','paid','overdue','cancelled'] as $st)
                <option value="{{ $st }}" {{ $status === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs text-ink/55 mb-1">Tenant</label>
        <select name="tenant_id" class="border border-ink/10 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">Semua tenant</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ $tenant == $t->id ? 'selected' : '' }}>{{ $t->name }} ({{ $t->code }})</option>
            @endforeach
        </select>
    </div>
    <button class="px-4 py-2 bg-ink text-white rounded-xl text-sm">Filter</button>
    @if($status || $tenant)
        <a href="{{ route('tenant_billing.invoices.index') }}" class="px-4 py-2 rounded-xl text-sm text-ink/60 hover:bg-cream-deep/60">Reset</a>
    @endif
</form>

<div class="bg-white rounded-3xl shadow-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-deep/60 text-ink/70">
            <tr>
                <th class="text-left px-5 py-3">Invoice</th>
                <th class="text-left px-5 py-3">Tenant</th>
                <th class="text-left px-5 py-3">Plan</th>
                <th class="text-left px-5 py-3">Periode</th>
                <th class="text-right px-5 py-3">Amount</th>
                <th class="text-left px-5 py-3">Due</th>
                <th class="text-left px-5 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-cream-deep/40">
        @forelse($invoices as $inv)
            <tr class="hover:bg-cream-deep/30">
                <td class="px-5 py-3 font-mono text-xs"><a href="{{ route('tenant_billing.invoices.show', $inv) }}" class="hover:underline font-semibold">{{ $inv->invoice_number }}</a></td>
                <td class="px-5 py-3">{{ $inv->tenant?->name ?? '—' }}</td>
                <td class="px-5 py-3 text-xs">{{ $inv->plan?->name ?? '—' }}</td>
                <td class="px-5 py-3 text-xs">{{ $inv->period_start?->format('d M') }} - {{ $inv->period_end?->format('d M Y') }}</td>
                <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                <td class="px-5 py-3 text-xs">{{ $inv->due_date?->format('d M Y') }}</td>
                <td class="px-5 py-3">
                    @php
                        $color = match($inv->status) {
                            'paid' => 'bg-emerald-50 text-emerald-700',
                            'overdue' => 'bg-rose-50 text-rose-700',
                            'cancelled' => 'bg-ink/10 text-ink/60',
                            default => 'bg-amber-50 text-amber-700',
                        };
                    @endphp
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $color }}">{{ $inv->status }}</span>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-ink/45 py-6">Tidak ada invoice.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>
@endsection
