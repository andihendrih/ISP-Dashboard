@extends('layouts.app')
@section('title','Tiket Support')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-1">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Tiket Support</h1>
        <p class="text-sm text-ink/55">Komplain & permintaan pelanggan AHNet.</p>
    </div>
    <a href="{{ route('tickets.create') }}" class="px-5 py-2.5 bg-ink text-cream rounded-xl font-semibold hover:brightness-95">+ Tiket Baru</a>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 my-3 text-sm">{{ session('success') }}</div>
@endif

{{-- Stat tiles --}}
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 my-4">
    <a href="?status=open" class="bg-white border border-cream-deep/60 rounded-2xl p-4 hover:border-ink/20">
        <div class="text-xs text-ink/55 uppercase tracking-wide font-medium">Open</div>
        <div class="text-2xl font-bold mt-1">{{ $stats['open'] }}</div>
    </a>
    <a href="?status=in_progress" class="bg-white border border-cream-deep/60 rounded-2xl p-4 hover:border-ink/20">
        <div class="text-xs text-ink/55 uppercase tracking-wide font-medium">In Progress</div>
        <div class="text-2xl font-bold mt-1 text-blue-600">{{ $stats['in_progress'] }}</div>
    </a>
    <a href="?status=pending_customer" class="bg-white border border-cream-deep/60 rounded-2xl p-4 hover:border-ink/20">
        <div class="text-xs text-ink/55 uppercase tracking-wide font-medium">Menunggu Pelanggan</div>
        <div class="text-2xl font-bold mt-1 text-amber-600">{{ $stats['pending'] }}</div>
    </a>
    <a href="?status=resolved" class="bg-white border border-cream-deep/60 rounded-2xl p-4 hover:border-ink/20">
        <div class="text-xs text-ink/55 uppercase tracking-wide font-medium">Resolved</div>
        <div class="text-2xl font-bold mt-1 text-emerald-600">{{ $stats['resolved'] }}</div>
    </a>
</div>

<form method="GET" class="mb-4 flex gap-2 flex-wrap">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nomor, subject, pelanggan…" class="flex-1 max-w-md border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
    <select name="status" class="border border-ink/10 rounded-xl text-sm bg-white px-3">
        <option value="">— Semua Status —</option>
        @foreach(['open' => 'Open', 'in_progress' => 'In Progress', 'pending_customer' => 'Menunggu Pelanggan', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $k => $v)
            <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
        @endforeach
    </select>
    <select name="category" class="border border-ink/10 rounded-xl text-sm bg-white px-3">
        <option value="">— Semua Kategori —</option>
        @foreach(['gangguan','billing','instalasi','pindah_alamat','upgrade','lainnya'] as $c)
            <option value="{{ $c }}" @selected(request('category')===$c)>{{ ucfirst(str_replace('_',' ',$c)) }}</option>
        @endforeach
    </select>
    <select name="priority" class="border border-ink/10 rounded-xl text-sm bg-white px-3">
        <option value="">— Semua Prioritas —</option>
        @foreach(['low','normal','high','urgent'] as $p)
            <option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>
        @endforeach
    </select>
    <button class="px-4 py-2 bg-ink text-cream rounded-xl text-sm">Filter</button>
</form>

<div class="bg-white rounded-2xl border border-cream-deep/60 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="text-ink/55 text-xs uppercase tracking-wide">
            <tr class="border-b border-cream-deep/60 bg-cream-deep/30">
                <th class="text-left px-5 py-3">No. Tiket</th>
                <th class="text-left px-5 py-3">Subject</th>
                <th class="text-left px-5 py-3">Pelanggan</th>
                <th class="text-left px-5 py-3">Kategori</th>
                <th class="text-left px-5 py-3">Prioritas</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-left px-5 py-3">Assignee</th>
                <th class="text-left px-5 py-3">Dibuat</th>
            </tr>
        </thead>
        <tbody>
        @forelse($tickets as $tk)
            @php
                $sBadge = match($tk->status){
                    'open' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'pending_customer' => 'bg-orange-50 text-orange-700 border-orange-200',
                    'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
                    default => 'bg-cream-deep/60 text-ink/60 border-cream-deep',
                };
                $pBadge = match($tk->priority){
                    'urgent' => 'bg-red-50 text-red-700 border-red-200',
                    'high'   => 'bg-rose-50 text-rose-700 border-rose-200',
                    'normal' => 'bg-cream-deep/60 text-ink/65 border-cream-deep',
                    'low'    => 'bg-slate-100 text-slate-500 border-slate-200',
                    default  => 'bg-cream-deep/60 text-ink/60',
                };
            @endphp
            <tr class="border-b border-cream-deep/40 hover:bg-cream-deep/20">
                <td class="px-5 py-3 font-mono text-xs">
                    <a href="{{ route('tickets.show', $tk) }}" class="text-ink hover:underline font-semibold">{{ $tk->ticket_number }}</a>
                </td>
                <td class="px-5 py-3">
                    <a href="{{ route('tickets.show', $tk) }}" class="hover:underline">{{ \Illuminate\Support\Str::limit($tk->subject, 60) }}</a>
                </td>
                <td class="px-5 py-3">
                    @if($tk->customer)
                        <div class="text-xs">{{ $tk->customer->customer_code }}</div>
                        <div>{{ $tk->customer->full_name }}</div>
                    @else
                        <span class="text-ink/40">—</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-xs uppercase">{{ str_replace('_',' ',$tk->category) }}</td>
                <td class="px-5 py-3"><span class="inline-block text-xs px-2 py-0.5 rounded-full border {{ $pBadge }}">{{ ucfirst($tk->priority) }}</span></td>
                <td class="px-5 py-3"><span class="inline-block text-xs px-2 py-0.5 rounded-full border {{ $sBadge }}">{{ $tk->status_label }}</span></td>
                <td class="px-5 py-3 text-xs">{{ $tk->assignee?->name ?? '—' }}</td>
                <td class="px-5 py-3 text-xs text-ink/55 whitespace-nowrap">{{ $tk->created_at->diffForHumans() }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center py-12 text-ink/40">
                Belum ada tiket. <a href="{{ route('tickets.create') }}" class="underline">Buat tiket pertama →</a>
            </td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $tickets->links() }}</div>
@endsection
