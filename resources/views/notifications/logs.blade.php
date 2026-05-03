@extends('layouts.app')
@section('title','Log Notifikasi')

@section('content')
<div class="flex items-start justify-between gap-3 mb-4">
    <div>
        <h1 class="text-2xl font-bold">Log Notifikasi</h1>
        <p class="text-sm text-ink/55">Audit trail semua notifikasi WA &amp; Email yang dikirim.</p>
    </div>
    <a href="{{ route('notifications.settings') }}" class="px-4 py-2 bg-cream-deep/60 text-ink rounded-xl text-sm font-semibold hover:bg-cream-deep">⚙ Pengaturan</a>
</div>

<form method="GET" class="bg-white border border-cream-deep/60 rounded-2xl p-3 mb-4 flex gap-2 items-end">
    <div>
        <label class="text-xs text-ink/55">Channel</label>
        <select name="channel" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
            <option value="">Semua</option>
            <option value="wa"    @selected(request('channel')==='wa')>WhatsApp</option>
            <option value="email" @selected(request('channel')==='email')>Email</option>
        </select>
    </div>
    <div>
        <label class="text-xs text-ink/55">Status</label>
        <select name="status" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
            <option value="">Semua</option>
            <option value="sent"    @selected(request('status')==='sent')>Sent</option>
            <option value="failed"  @selected(request('status')==='failed')>Failed</option>
            <option value="pending" @selected(request('status')==='pending')>Pending</option>
        </select>
    </div>
    <button class="px-4 py-2 bg-accent text-ink rounded-xl text-sm font-semibold">Filter</button>
    <a href="{{ route('notifications.logs') }}" class="px-3 py-2 text-sm text-ink/55 hover:bg-cream-deep/60 rounded-xl">Reset</a>
</form>

<div class="bg-white border border-cream-deep/60 rounded-2xl overflow-hidden">
    <table class="w-full text-sm">
        <thead class="text-ink/55 text-xs uppercase tracking-wide bg-cream-deep/30">
            <tr>
                <th class="text-left px-5 py-3">Waktu</th>
                <th class="text-left px-5 py-3">Channel</th>
                <th class="text-left px-5 py-3">Template</th>
                <th class="text-left px-5 py-3">Recipient</th>
                <th class="text-left px-5 py-3">Pelanggan</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-left px-5 py-3">Response</th>
            </tr>
        </thead>
        <tbody>
        @forelse($logs as $l)
            <tr class="border-b border-cream-deep/40 align-top">
                <td class="px-5 py-3 text-xs text-ink/65">{{ $l->created_at->format('d M H:i') }}</td>
                <td class="px-5 py-3 text-xs uppercase font-semibold">{{ $l->channel }}</td>
                <td class="px-5 py-3 text-xs font-mono">{{ $l->template }}</td>
                <td class="px-5 py-3 text-xs">{{ $l->recipient }}</td>
                <td class="px-5 py-3 text-xs">{{ $l->customer?->full_name ?? '—' }}</td>
                <td class="px-5 py-3">
                    @php
                        $color = match($l->status) {
                            'sent'    => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            'failed'  => 'bg-red-50 text-red-700 border-red-200',
                            default   => 'bg-amber-50 text-amber-700 border-amber-200',
                        };
                    @endphp
                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold border {{ $color }}">{{ ucfirst($l->status) }}</span>
                </td>
                <td class="px-5 py-3 text-xs text-ink/65 max-w-md truncate" title="{{ $l->provider_response }}">{{ \Illuminate\Support\Str::limit($l->provider_response, 80) }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-12 text-center text-ink/40">Belum ada log notifikasi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $logs->links() }}</div>
@endsection
