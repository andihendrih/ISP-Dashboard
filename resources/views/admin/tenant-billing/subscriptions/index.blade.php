@extends('layouts.app')
@section('title','Tenant Billing — Subscriptions')
@section('breadcrumb','Tenant Billing / Subscriptions')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Subscriptions</h1>
        <p class="text-sm text-ink/55">Tenant yang sedang subscribe ke plan.</p>
    </div>
</div>

@if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>@endif

{{-- Subscribe form --}}
<div class="bg-white rounded-3xl shadow-card p-4 sm:p-6 mb-4">
    <h2 class="font-bold text-sm mb-3">Subscribe Tenant ke Plan</h2>
    <form method="POST" action="{{ route('tenant_billing.subscriptions.subscribe') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3 items-end">
        @csrf
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-ink/65 mb-1">Tenant</label>
            <select name="tenant_id" required class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                <option value="">— pilih tenant —</option>
                @foreach($tenants as $t)
                    <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Plan</label>
            <select name="plan_id" required class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                @foreach($plans as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} — Rp {{ number_format($p->price, 0, ',', '.') }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Override Harga (opsional)</label>
            <input type="number" name="price_override" min="0" placeholder="kosong = pakai plan" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
        </div>
        <div>
            <button class="w-full px-4 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Subscribe</button>
        </div>
    </form>
    <p class="text-xs text-ink/45 mt-2">Subscribe baru akan otomatis bikin invoice pertama (prorated kalau mid-month).</p>
</div>

<div class="bg-white rounded-3xl shadow-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-deep/60 text-ink/70">
            <tr>
                <th class="text-left px-5 py-3">Tenant</th>
                <th class="text-left px-5 py-3">Plan</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-right px-5 py-3">Harga</th>
                <th class="text-left px-5 py-3">Started</th>
                <th class="text-left px-5 py-3">Next Billing</th>
                <th class="text-right px-5 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-cream-deep/40">
        @forelse($subs as $s)
            <tr>
                <td class="px-5 py-3">
                    <div class="font-semibold">{{ $s->tenant?->name ?? '—' }}</div>
                    <div class="text-xs text-ink/45 font-mono">{{ $s->tenant?->code }}</div>
                </td>
                <td class="px-5 py-3">{{ $s->plan?->name ?? '—' }}</td>
                <td class="px-5 py-3">
                    @php
                        $color = match($s->status) {
                            'active' => 'bg-emerald-50 text-emerald-700',
                            'past_due' => 'bg-amber-50 text-amber-700',
                            'suspended' => 'bg-rose-50 text-rose-700',
                            'cancelled' => 'bg-ink/10 text-ink/60',
                            default => 'bg-sky-50 text-sky-700',
                        };
                    @endphp
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $color }}">{{ $s->status }}</span>
                </td>
                <td class="px-5 py-3 text-right font-mono">Rp {{ number_format($s->effectivePrice(), 0, ',', '.') }}</td>
                <td class="px-5 py-3 text-xs">{{ $s->started_at?->format('d M Y') }}</td>
                <td class="px-5 py-3 text-xs">{{ $s->next_billing_at?->format('d M Y') }}</td>
                <td class="px-5 py-3 text-right">
                    @if(!in_array($s->status, ['cancelled', 'suspended']))
                        <form method="POST" action="{{ route('tenant_billing.subscriptions.cancel', $s) }}" class="inline" onsubmit="return confirm('Batalkan subscription {{ $s->tenant?->name }}?')">
                            @csrf
                            <button class="text-xs text-rose-700 hover:underline">Cancel</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-ink/45 py-6">Belum ada subscription.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $subs->links() }}</div>
@endsection
