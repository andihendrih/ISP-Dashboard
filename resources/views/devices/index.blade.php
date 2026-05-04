@extends('layouts.app')
@section('title','Inventory Perangkat')
@section('breadcrumb','Inventory')

@section('content')
@php
    $typeLabels = [
        'onu' => 'ONU', 'router' => 'Router', 'switch' => 'Switch',
        'ap' => 'AP', 'radio' => 'Radio', 'cable' => 'Cable', 'other' => 'Lainnya',
    ];
    $statusLabels = [
        'stock' => 'Stock', 'assigned' => 'Terpasang', 'rusak' => 'Rusak',
        'hilang' => 'Hilang', 'retired' => 'Pensiun',
    ];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Inventory Perangkat</h1>
        <p class="text-sm text-slate-500">Stok &amp; history ONU / router / perangkat jaringan.</p>
    </div>
    <a href="{{ route('devices.create') }}" class="px-4 py-2 bg-slate-800 text-white rounded-xl hover:bg-black text-sm whitespace-nowrap self-start sm:self-auto">+ Tambah Perangkat</a>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

{{-- Stat tiles --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm">
        <div class="text-xs text-slate-500 font-medium">Total Perangkat</div>
        <div class="text-2xl font-extrabold mt-1">{{ number_format($stats['total'], 0, ',', '.') }}</div>
    </div>
    <div class="bg-sky-50 border border-sky-200 rounded-2xl p-4">
        <div class="text-xs text-sky-700 font-medium">Stock</div>
        <div class="text-2xl font-extrabold text-sky-900 mt-1">{{ number_format($stats['stock'], 0, ',', '.') }}</div>
    </div>
    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-4">
        <div class="text-xs text-emerald-700 font-medium">Terpasang</div>
        <div class="text-2xl font-extrabold text-emerald-900 mt-1">{{ number_format($stats['assigned'], 0, ',', '.') }}</div>
    </div>
    <div class="bg-rose-50 border border-rose-200 rounded-2xl p-4">
        <div class="text-xs text-rose-700 font-medium">Rusak</div>
        <div class="text-2xl font-extrabold text-rose-900 mt-1">{{ number_format($stats['rusak'], 0, ',', '.') }}</div>
    </div>
</div>

{{-- Filter --}}
<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Cari serial / MAC / brand / model"
           class="flex-1 min-w-[200px] sm:max-w-md border border-slate-300 rounded-xl text-sm px-3 py-2 bg-white">
    <select name="type" class="border border-slate-300 rounded-xl text-sm bg-white px-3">
        <option value="">— Semua Tipe —</option>
        @foreach($typeLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('type') === $k)>{{ $v }}</option>
        @endforeach
    </select>
    <select name="status" class="border border-slate-300 rounded-xl text-sm bg-white px-3">
        <option value="">— Semua Status —</option>
        @foreach($statusLabels as $k => $v)
            <option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>
        @endforeach
    </select>
    <button class="px-4 py-2 bg-slate-700 text-white rounded-xl text-sm">Filter</button>
    @if(request()->hasAny(['q','type','status']))
        <a href="{{ route('devices.index') }}" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-xl text-sm bg-white">Reset</a>
    @endif
</form>

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs uppercase tracking-wide">
            <tr>
                <th class="text-left px-4 py-3">Serial</th>
                <th class="text-left px-4 py-3">Tipe</th>
                <th class="text-left px-4 py-3">Brand / Model</th>
                <th class="text-left px-4 py-3">MAC</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Dipasang</th>
                <th class="text-right px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                @php($badge = $r->statusBadge())
                <tr class="border-t border-slate-100 hover:bg-slate-50/60">
                    <td class="px-4 py-2 font-mono text-xs">{{ $r->serial_number }}</td>
                    <td class="px-4 py-2"><span class="text-xs bg-slate-100 border border-slate-200 rounded-full px-2 py-0.5">{{ $typeLabels[$r->type] ?? $r->type }}</span></td>
                    <td class="px-4 py-2">
                        <div>{{ $r->brand ?: '—' }}</div>
                        <div class="text-xs text-slate-500">{{ $r->model ?: '' }}</div>
                    </td>
                    <td class="px-4 py-2 font-mono text-xs">{{ $r->mac_address ?: '—' }}</td>
                    <td class="px-4 py-2"><span class="text-xs rounded-full px-2 py-0.5 border {{ $badge['class'] }}">{{ $badge['label'] }}</span></td>
                    <td class="px-4 py-2">
                        @if($r->customer)
                            <a href="{{ route('customers.edit', $r->customer_id) }}" class="text-blue-600 hover:underline">
                                {{ $r->customer->full_name }}
                            </a>
                            <div class="text-xs text-slate-500 font-mono">{{ $r->customer->customer_code }}</div>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right">
                        <a href="{{ route('devices.show', $r) }}" class="text-blue-600 text-xs hover:underline">Detail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-10 text-center text-slate-400">
                    Belum ada perangkat. <a href="{{ route('devices.create') }}" class="text-blue-600 hover:underline">Tambah perangkat</a>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($rows->total() > 0)
        <div class="px-4 py-3 border-t border-slate-100">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
