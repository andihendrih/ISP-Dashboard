@extends('layouts.app')
@section('title','Tiket ' . $ticket->ticket_number)

@php
    $sBadge = match($ticket->status){
        'open' => 'bg-amber-50 text-amber-700 border-amber-200',
        'in_progress' => 'bg-blue-50 text-blue-700 border-blue-200',
        'pending_customer' => 'bg-orange-50 text-orange-700 border-orange-200',
        'resolved' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'closed' => 'bg-slate-100 text-slate-600 border-slate-200',
        default => 'bg-cream-deep/60 text-ink/60',
    };
    $pBadge = match($ticket->priority){
        'urgent' => 'bg-red-100 text-red-700 border-red-300',
        'high'   => 'bg-rose-50 text-rose-700 border-rose-200',
        'normal' => 'bg-cream-deep/60 text-ink/65 border-cream-deep',
        'low'    => 'bg-slate-100 text-slate-500 border-slate-200',
    };
@endphp

@section('content')
<div class="mb-4 flex items-start justify-between gap-3">
    <div>
        <div class="text-xs text-ink/55 font-mono">{{ $ticket->ticket_number }}</div>
        <h1 class="text-xl sm:text-2xl font-bold">{{ $ticket->subject }}</h1>
        <div class="flex gap-2 mt-2">
            <span class="text-xs px-2 py-0.5 rounded-full border {{ $sBadge }}">{{ $ticket->status_label }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full border {{ $pBadge }}">Prioritas: {{ ucfirst($ticket->priority) }}</span>
            <span class="text-xs px-2 py-0.5 rounded-full border bg-cream-deep/60 text-ink/65 border-cream-deep uppercase">{{ str_replace('_',' ',$ticket->category) }}</span>
        </div>
    </div>
    <a href="{{ route('tickets.index') }}" class="px-4 py-2 bg-white border border-ink/10 rounded-xl text-sm">← Daftar</a>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    {{-- Konten utama --}}
    <div class="col-span-2 space-y-4">
        {{-- Original ticket --}}
        <div class="bg-white border border-cream-deep/60 rounded-2xl p-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-2">
                <div class="text-sm font-semibold">{{ $ticket->creator?->name ?? 'System' }}</div>
                <div class="text-xs text-ink/50">{{ $ticket->created_at->format('d M Y, H:i') }}</div>
            </div>
            <div class="whitespace-pre-wrap text-sm leading-relaxed text-ink/85">{{ $ticket->description }}</div>
        </div>

        {{-- Comments thread --}}
        @foreach($ticket->comments as $cmt)
            <div class="border rounded-2xl p-4 {{ $cmt->is_internal ? 'bg-amber-50/40 border-amber-200' : 'bg-white border-cream-deep/60' }}">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-full bg-accent/40 grid place-items-center text-xs font-bold">{{ strtoupper(substr($cmt->user?->name ?? 'S', 0, 1)) }}</div>
                        <div class="text-sm font-medium">{{ $cmt->user?->name ?? 'System' }}</div>
                        @if($cmt->is_internal)
                            <span class="text-[10px] uppercase bg-amber-200 text-amber-900 rounded-full px-2 py-0.5 font-bold">Internal Note</span>
                        @endif
                    </div>
                    <div class="text-xs text-ink/50">{{ $cmt->created_at->diffForHumans() }}</div>
                </div>
                <div class="whitespace-pre-wrap text-sm leading-relaxed">{{ $cmt->body }}</div>
            </div>
        @endforeach

        {{-- Reply form --}}
        @if(!$ticket->is_closed || $ticket->status === 'resolved')
            <form method="POST" action="{{ route('tickets.comment', $ticket) }}" class="bg-white border border-cream-deep/60 rounded-2xl p-4">
                @csrf
                <textarea name="body" rows="4" required placeholder="Tulis balasan / update progress…" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm"></textarea>
                <div class="flex items-center justify-between mt-3">
                    <label class="inline-flex items-center gap-2 text-sm">
                        <input type="hidden" name="is_internal" value="0">
                        <input type="checkbox" name="is_internal" value="1" class="rounded">
                        <span>Internal note <span class="text-ink/45">(tidak terlihat oleh pelanggan)</span></span>
                    </label>
                    <button class="px-5 py-2 bg-ink text-cream rounded-xl text-sm font-semibold">Kirim Balasan</button>
                </div>
            </form>
        @endif
    </div>

    {{-- Sidebar info --}}
    <div class="space-y-4">
        {{-- Pelanggan --}}
        <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
            <div class="text-xs text-ink/55 uppercase tracking-wide mb-2">Pelanggan</div>
            @if($ticket->customer)
                <div class="font-semibold">{{ $ticket->customer->full_name }}</div>
                <div class="text-xs text-ink/55 font-mono">{{ $ticket->customer->customer_code }}</div>
                @if($ticket->customer->phone)<div class="text-xs mt-1">📞 {{ $ticket->customer->phone }}</div>@endif
                @if($ticket->customer->servicePlan)<div class="text-xs mt-1">📦 {{ $ticket->customer->servicePlan->name }}</div>@endif
                @if($ticket->customer->radius_username)<div class="text-xs mt-1 font-mono">👤 {{ $ticket->customer->radius_username }}</div>@endif
                @if($ticket->customer->address)<div class="text-xs mt-1 text-ink/55">📍 {{ $ticket->customer->address }}</div>@endif
                <a href="{{ route('customers.edit', $ticket->customer->id) }}" class="text-xs underline mt-2 inline-block">Buka pelanggan →</a>
            @else
                <div class="text-sm text-ink/45">— Tidak terkait pelanggan</div>
            @endif
            @if($ticket->contact_phone && $ticket->contact_phone !== $ticket->customer?->phone)
                <div class="text-xs mt-2 pt-2 border-t border-cream-deep/60">Kontak tiket: {{ $ticket->contact_phone }}</div>
            @endif
        </div>

        {{-- Status --}}
        <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="bg-white border border-cream-deep/60 rounded-2xl p-4">
            @csrf
            <div class="text-xs text-ink/55 uppercase tracking-wide mb-2">Status</div>
            <select name="status" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                @foreach(['open' => 'Open', 'in_progress' => 'In Progress', 'pending_customer' => 'Menunggu Pelanggan', 'resolved' => 'Resolved', 'closed' => 'Closed'] as $k => $v)
                    <option value="{{ $k }}" @selected($ticket->status === $k)>{{ $v }}</option>
                @endforeach
            </select>
            <textarea name="resolution" rows="2" placeholder="Catatan resolusi (kalau resolved)" class="w-full mt-2 border border-ink/10 rounded-lg px-3 py-2 text-sm">{{ $ticket->resolution }}</textarea>
            <button class="w-full mt-2 px-3 py-2 bg-ink text-cream rounded-lg text-sm font-semibold">Update Status</button>
        </form>

        {{-- Assignment --}}
        <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="bg-white border border-cream-deep/60 rounded-2xl p-4">
            @csrf
            <div class="text-xs text-ink/55 uppercase tracking-wide mb-2">Assignee</div>
            <select name="assigned_to" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                <option value="">— Belum di-assign —</option>
                @foreach($staff as $u)
                    <option value="{{ $u->id }}" @selected($ticket->assigned_to === $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
            <button class="w-full mt-2 px-3 py-2 bg-cream-deep/70 hover:bg-cream-deep rounded-lg text-sm font-semibold">Update Assignment</button>
        </form>

        {{-- Timeline --}}
        <div class="bg-white border border-cream-deep/60 rounded-2xl p-4 text-xs space-y-1.5">
            <div class="text-ink/55 uppercase tracking-wide font-medium">Timeline</div>
            <div>📅 Dibuat: <strong>{{ $ticket->created_at->format('d M Y, H:i') }}</strong></div>
            @if($ticket->first_response_at)
                <div>💬 Respons pertama: <strong>{{ $ticket->first_response_at->diffForHumans($ticket->created_at, true) }}</strong></div>
            @endif
            @if($ticket->resolved_at)
                <div>✅ Resolved: <strong>{{ $ticket->resolved_at->format('d M Y, H:i') }}</strong></div>
            @endif
            @if($ticket->closed_at)
                <div>🔒 Closed: <strong>{{ $ticket->closed_at->format('d M Y, H:i') }}</strong></div>
            @endif
            @if($ticket->creator)
                <div class="pt-1 border-t border-cream-deep/60 mt-2">Dibuat oleh: <strong>{{ $ticket->creator->name }}</strong></div>
            @endif
        </div>
    </div>
</div>
@endsection
