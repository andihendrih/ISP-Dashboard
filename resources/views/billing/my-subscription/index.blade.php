@extends('layouts.app')
@section('title','Langganan Saya')
@section('breadcrumb','Billing / Langganan Saya')

@section('content')
<div class="flex items-center justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Langganan Portal</h1>
        <p class="text-sm text-ink/55">Status subscription &amp; tagihan tenant ke platform.</p>
    </div>
</div>

@if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif

@if($sub)
    <div class="bg-white rounded-3xl shadow-card p-5 mb-4">
        <div class="flex items-start justify-between gap-2 flex-wrap">
            <div>
                <div class="text-xs text-ink/55">Plan saat ini</div>
                <div class="text-2xl font-bold">{{ $sub->plan?->name ?? '—' }}</div>
                <div class="text-sm text-ink/65 mt-1">Rp {{ number_format($sub->effectivePrice(), 0, ',', '.') }} /bulan</div>
            </div>
            <div>
                @php
                    $color = match($sub->status) {
                        'active' => 'bg-emerald-50 text-emerald-700',
                        'past_due' => 'bg-amber-50 text-amber-700',
                        'suspended' => 'bg-rose-50 text-rose-700',
                        'cancelled' => 'bg-ink/10 text-ink/60',
                        default => 'bg-sky-50 text-sky-700',
                    };
                @endphp
                <span class="text-xs px-3 py-1 rounded-full {{ $color }}">{{ $sub->status }}</span>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-4 text-sm">
            <div><div class="text-xs text-ink/55">Started</div><div>{{ $sub->started_at?->format('d M Y') }}</div></div>
            <div><div class="text-xs text-ink/55">Next Billing</div><div>{{ $sub->next_billing_at?->format('d M Y') }}</div></div>
            <div><div class="text-xs text-ink/55">Max Pelanggan</div><div>{{ $sub->plan?->max_customers ? number_format($sub->plan->max_customers, 0, ',', '.') : 'unlimited' }}</div></div>
            <div><div class="text-xs text-ink/55">Max Perangkat</div><div>{{ $sub->plan?->max_devices ? number_format($sub->plan->max_devices, 0, ',', '.') : 'unlimited' }}</div></div>
        </div>
        @if($sub->status === 'suspended')
            <div class="mt-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl p-3 text-sm">
                ⚠️ Tenant ini sedang <b>suspended</b>. Lunasin invoice yang overdue di bawah untuk reaktivasi otomatis.
            </div>
        @endif
    </div>
@else
    <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl p-4 mb-4 text-sm">
        Tenant ini belum punya subscription. Hubungi superadmin platform untuk subscribe ke plan.
    </div>
@endif

<div class="bg-white rounded-3xl shadow-card overflow-hidden">
    <div class="px-5 py-3 border-b border-cream-deep/60">
        <h2 class="font-bold text-sm">Tagihan</h2>
    </div>
    <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-deep/40 text-ink/70">
            <tr>
                <th class="text-left px-5 py-2 text-xs">Invoice</th>
                <th class="text-left px-5 py-2 text-xs">Periode</th>
                <th class="text-right px-5 py-2 text-xs">Amount</th>
                <th class="text-left px-5 py-2 text-xs">Due</th>
                <th class="text-left px-5 py-2 text-xs">Status</th>
                <th class="text-right px-5 py-2 text-xs">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-cream-deep/40">
        @forelse($invoices as $inv)
            <tr>
                <td class="px-5 py-3 font-mono text-xs">{{ $inv->invoice_number }}</td>
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
                <td class="px-5 py-3 text-right">
                    <a href="{{ route('my_subscription.invoices.show', $inv) }}" class="text-xs text-ink hover:underline">Lihat →</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-ink/45 py-6">Belum ada tagihan.</td></tr>
        @endforelse
        </tbody>
    </table>
    </div>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>
@endsection
