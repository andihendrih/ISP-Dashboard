@extends('layouts.app')
@section('title','TR-069 Management')
@section('breadcrumb','Network / TR-069')

@section('content')
@php
    $statusFilter = request('status');
    $isOnlineFilter = $statusFilter === 'online';
    $isOfflineFilter = $statusFilter === 'offline';
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h6m-6-6V3m0 4l-2-2m2 2l2-2M5 21h14a2 2 0 002-2v-7a2 2 0 00-2-2H5a2 2 0 00-2 2v7a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <h1 class="text-xl sm:text-2xl font-bold flex items-center gap-2">
                TR-069 Management
                <span class="inline-block w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
            </h1>
            <p class="text-xs text-ink/55">Manajemen ONU/CPE via GenieACS NBI.</p>
        </div>
    </div>
    <div class="flex flex-wrap gap-2 items-center">
        <a href="{{ route('genieacs.export-csv') }}" class="px-3 py-1.5 border border-ink/10 bg-white rounded-xl text-xs font-medium hover:bg-cream-card/40 inline-flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
            CSV
        </a>
        <a href="{{ route('genieacs.export-csv') }}" class="px-3 py-1.5 border border-ink/10 bg-white rounded-xl text-xs font-medium hover:bg-cream-card/40 inline-flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-6h13v6M9 11l3-3 3 3M3 7h18"/></svg>
            Excel
        </a>
        <form method="POST" action="{{ route('genieacs.sync') }}" class="inline">
            @csrf
            <button class="px-3 py-1.5 border border-ink/10 bg-white rounded-xl text-xs font-medium hover:bg-cream-card/40 inline-flex items-center gap-1" title="Sync ulang dari GenieACS NBI">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0114-2M20 15a8 8 0 01-14 2"/></svg>
            </button>
        </form>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

{{-- Stat chips --}}
<div class="flex flex-wrap gap-2 mb-4">
    <a href="{{ route('genieacs.index', ['q' => request('q')]) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-sm font-medium border {{ !$statusFilter ? 'border-blue-300 bg-blue-50 text-blue-900' : 'border-ink/10 bg-white text-ink/70 hover:bg-cream-card/40' }}">
        Total <span class="px-2 py-0.5 rounded-full text-xs {{ !$statusFilter ? 'bg-blue-200 text-blue-900' : 'bg-ink/5 text-ink/60' }}">{{ number_format($stats['total']) }}</span>
    </a>
    <a href="{{ route('genieacs.index', ['status' => 'online', 'q' => request('q')]) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-sm font-medium border {{ $isOnlineFilter ? 'border-emerald-300 bg-emerald-50 text-emerald-900' : 'border-ink/10 bg-white text-ink/70 hover:bg-cream-card/40' }}">
        Online <span class="px-2 py-0.5 rounded-full text-xs {{ $isOnlineFilter ? 'bg-emerald-200 text-emerald-900' : 'bg-emerald-100 text-emerald-700' }}">{{ number_format($stats['online']) }}</span>
    </a>
    <a href="{{ route('genieacs.index', ['status' => 'offline', 'q' => request('q')]) }}"
       class="inline-flex items-center gap-2 px-4 py-2 rounded-2xl text-sm font-medium border {{ $isOfflineFilter ? 'border-ink/30 bg-ink/5 text-ink' : 'border-ink/10 bg-white text-ink/70 hover:bg-cream-card/40' }}">
        Offline <span class="px-2 py-0.5 rounded-full text-xs {{ $isOfflineFilter ? 'bg-ink/20 text-ink' : 'bg-ink/5 text-ink/60' }}">{{ number_format($stats['offline']) }}</span>
    </a>
</div>

{{-- Search --}}
<form method="GET" class="mb-4">
    @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
    <div class="relative">
        <input name="q" value="{{ request('q') }}"
               placeholder="Cari nama client, tags, serial, PPPoE, model..."
               class="w-full border border-ink/10 rounded-2xl text-sm px-10 py-2.5 bg-white shadow-card focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
        <svg class="w-4 h-4 absolute left-3.5 top-3 text-ink/40" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4-4m1-7a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </div>
</form>

<div class="bg-white rounded-3xl shadow-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-deep/40 text-ink/60 text-xs uppercase tracking-wide">
            <tr>
                <th class="text-left px-4 py-3">
                    <input type="checkbox" class="rounded border-ink/20" disabled>
                </th>
                <th class="text-left px-4 py-3">Tags</th>
                <th class="text-left px-4 py-3">Model</th>
                <th class="text-left px-4 py-3">Serial Number</th>
                <th class="text-left px-4 py-3">PPPoE</th>
                <th class="text-right px-4 py-3">Redaman</th>
                <th class="text-center px-4 py-3">Status</th>
                <th class="text-center px-4 py-3">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($devices as $d)
                @php
                    $tags = $d->tagsList();
                    $rxPower = $d->rxPower();
                    $pppoeUser = $d->pppoeUsername();
                    $isOnline = $d->status === 'online';
                @endphp
                <tr class="border-t border-cream-deep/60 hover:bg-cream-card/30">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="rounded border-ink/20">
                    </td>
                    <td class="px-4 py-3">
                        @forelse($tags as $tg)
                            <span class="inline-block bg-blue-50 text-blue-700 border border-blue-100 rounded-full px-2 py-0.5 text-xs">{{ $tg }}</span>
                        @empty
                            <span class="text-ink/30 text-xs">—</span>
                        @endforelse
                    </td>
                    <td class="px-4 py-3 text-ink/80">
                        {{ $d->product_class ?: $d->model_name ?: '—' }}
                    </td>
                    <td class="px-4 py-3 font-mono text-xs">
                        <span class="inline-block w-2 h-2 rounded-full {{ $isOnline ? 'bg-emerald-500' : 'bg-ink/20' }} mr-1.5 align-middle"></span>
                        {{ $d->serial_number ?: substr($d->device_id, 0, 28) }}
                    </td>
                    <td class="px-4 py-3 text-xs">
                        @if($pppoeUser)
                            <span class="font-mono">{{ $pppoeUser }}</span>
                        @else
                            <span class="text-ink/30">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right">
                        @if($rxPower !== null)
                            @php
                                $rxClass = $rxPower >= -25 ? 'text-emerald-600' : ($rxPower >= -28 ? 'text-amber-600' : 'text-rose-600');
                            @endphp
                            <span class="font-mono text-xs font-semibold {{ $rxClass }}">{{ number_format($rxPower, 0) }} dBm</span>
                        @else
                            <span class="text-ink/30 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        @if($isOnline)
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Online
                            </span>
                        @elseif($d->status === 'offline')
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-ink/5 text-ink/60 border border-ink/10">
                                Offline
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs bg-amber-50 text-amber-700 border border-amber-200">{{ $d->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('genieacs.show', $d) }}"
                           class="inline-flex items-center gap-1 px-3 py-1 border border-blue-200 text-blue-600 rounded-lg text-xs hover:bg-blue-50">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Detail
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-ink/40">
                        Belum ada device. Klik tombol refresh di atas untuk sync dari NBI.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    @if($devices->total() > 0)
        <div class="px-4 py-3 border-t border-cream-deep/60">{{ $devices->links() }}</div>
    @endif
</div>
@endsection
