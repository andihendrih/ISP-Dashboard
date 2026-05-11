@extends('layouts.app')
@section('title','Tenant Billing — Plans')
@section('breadcrumb','Tenant Billing / Plans')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Plans</h1>
        <p class="text-sm text-ink/55">Tier subscription untuk tenant — Basic / Pro / Enterprise atau custom.</p>
    </div>
    <a href="{{ route('tenant_billing.plans.create') }}" class="px-4 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">+ Plan Baru</a>
</div>

@if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>@endif

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
@foreach($plans as $p)
    <div class="bg-white rounded-3xl shadow-card p-5 flex flex-col">
        <div class="flex items-center justify-between mb-2">
            <h3 class="font-bold text-lg">{{ $p->name }}</h3>
            @if($p->is_active)
                <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700">aktif</span>
            @else
                <span class="text-xs px-2 py-0.5 rounded-full bg-ink/10 text-ink/60">nonaktif</span>
            @endif
        </div>
        <div class="text-xs text-ink/55 font-mono mb-2">{{ $p->code }}</div>
        <div class="text-3xl font-bold text-accent-strong mb-3">Rp {{ number_format($p->price, 0, ',', '.') }}<span class="text-sm text-ink/55 font-normal"> /bln</span></div>
        <ul class="text-sm space-y-1 mb-4">
            <li>👥 Max pelanggan: <span class="font-semibold">{{ $p->max_customers ? number_format($p->max_customers, 0, ',', '.') : 'unlimited' }}</span></li>
            <li>📡 Max perangkat: <span class="font-semibold">{{ $p->max_devices ? number_format($p->max_devices, 0, ',', '.') : 'unlimited' }}</span></li>
            @foreach(($p->features ?? []) as $k => $v)
                <li class="text-xs text-ink/60">• {{ $k }}: <span class="font-mono">{{ is_bool($v) ? ($v ? 'yes' : 'no') : $v }}</span></li>
            @endforeach
        </ul>
        <div class="mt-auto flex items-center gap-2">
            <a href="{{ route('tenant_billing.plans.edit', $p) }}" class="flex-1 text-center px-3 py-2 bg-cream-deep/60 rounded-lg text-sm hover:bg-cream-deep">Edit</a>
            <form method="POST" action="{{ route('tenant_billing.plans.destroy', $p) }}" onsubmit="return confirm('Hapus plan {{ $p->name }}?')">
                @csrf @method('DELETE')
                <button class="px-3 py-2 bg-rose-50 text-rose-700 rounded-lg text-sm hover:bg-rose-100">Hapus</button>
            </form>
        </div>
    </div>
@endforeach
</div>
@endsection
