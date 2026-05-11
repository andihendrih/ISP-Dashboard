@extends('layouts.app')
@section('title','Tenant Billing — Dashboard')
@section('breadcrumb','Tenant Billing / Dashboard')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Tenant Billing</h1>
        <p class="text-sm text-ink/55">Tagihan langganan tenant — SaaS subscription overview.</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        <a href="{{ route('tenant_billing.plans.index') }}" class="px-4 py-2 bg-white border border-ink/10 rounded-xl text-sm hover:bg-cream-deep/40">Plans</a>
        <a href="{{ route('tenant_billing.subscriptions.index') }}" class="px-4 py-2 bg-white border border-ink/10 rounded-xl text-sm hover:bg-cream-deep/40">Subscriptions</a>
        <a href="{{ route('tenant_billing.invoices.index') }}" class="px-4 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Invoices</a>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">MRR (Monthly Recurring Revenue)</div>
        <div class="text-2xl font-bold text-accent-strong">Rp {{ number_format($totalMrr, 0, ',', '.') }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">Subscription Aktif</div>
        <div class="text-2xl font-bold text-emerald-700">{{ $activeCount }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">Suspended</div>
        <div class="text-2xl font-bold text-rose-600">{{ $suspendedCount }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">Invoice Unpaid</div>
        <div class="text-2xl font-bold text-amber-700">{{ $unpaidInvoices }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">Invoice Overdue</div>
        <div class="text-2xl font-bold text-rose-700">{{ $overdueInvoices }}</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-3xl shadow-card overflow-hidden">
        <div class="px-5 py-3 border-b border-cream-deep/60 flex items-center justify-between">
            <h2 class="font-bold text-sm">Invoice Terbaru</h2>
            <a href="{{ route('tenant_billing.invoices.index') }}" class="text-xs text-ink/55 hover:text-ink">Semua →</a>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-cream-deep/40 text-ink/70">
                <tr>
                    <th class="text-left px-4 py-2 text-xs">Invoice</th>
                    <th class="text-left px-4 py-2 text-xs">Tenant</th>
                    <th class="text-right px-4 py-2 text-xs">Amount</th>
                    <th class="text-left px-4 py-2 text-xs">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-deep/40">
            @forelse($recentInvoices as $inv)
                <tr>
                    <td class="px-4 py-2 font-mono text-xs"><a href="{{ route('tenant_billing.invoices.show', $inv) }}" class="hover:underline">{{ $inv->invoice_number }}</a></td>
                    <td class="px-4 py-2">{{ $inv->tenant?->name ?? '—' }}</td>
                    <td class="px-4 py-2 text-right font-mono">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                    <td class="px-4 py-2">
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
                <tr><td colspan="4" class="text-center text-ink/45 py-6 text-sm">Belum ada invoice.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white rounded-3xl shadow-card overflow-hidden">
        <div class="px-5 py-3 border-b border-cream-deep/60 flex items-center justify-between">
            <h2 class="font-bold text-sm">Tenant Overdue</h2>
            <span class="text-xs text-ink/55">Jatuh tempo lewat</span>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-cream-deep/40 text-ink/70">
                <tr>
                    <th class="text-left px-4 py-2 text-xs">Tenant</th>
                    <th class="text-left px-4 py-2 text-xs">Invoice</th>
                    <th class="text-left px-4 py-2 text-xs">Due</th>
                    <th class="text-right px-4 py-2 text-xs">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-cream-deep/40">
            @forelse($overdueTenantsList as $inv)
                <tr>
                    <td class="px-4 py-2">{{ $inv->tenant?->name ?? '—' }}</td>
                    <td class="px-4 py-2 font-mono text-xs"><a href="{{ route('tenant_billing.invoices.show', $inv) }}" class="hover:underline">{{ $inv->invoice_number }}</a></td>
                    <td class="px-4 py-2 text-rose-700">{{ \Illuminate\Support\Carbon::parse($inv->due_date)->format('d M Y') }}</td>
                    <td class="px-4 py-2 text-right font-mono">Rp {{ number_format($inv->amount, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-ink/45 py-6 text-sm">Gak ada tenant overdue.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
