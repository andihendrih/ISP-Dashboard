@extends('layouts.app')
@section('title', 'Detail User: '.$username)

@section('content')
<div class="flex items-center gap-3 mb-4">
    <h1 class="text-xl font-bold">{{ $username }}</h1>
    @if($online)
        <span class="px-2 py-0.5 rounded-full text-xs bg-emerald-100 text-emerald-700">online</span>
    @else
        <span class="px-2 py-0.5 rounded-full text-xs bg-cream-deep/60 text-ink/55">offline</span>
    @endif
    <span class="text-sm text-ink/55">Group: {{ $group ?? '—' }}</span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white rounded-xl border border-cream-deep/60 p-5">
        <div class="font-semibold mb-3">Customer Profile</div>
        @if($customer)
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                <dt class="text-ink/55">Nama</dt><dd>{{ $customer->full_name }}</dd>
                <dt class="text-ink/55">Telp</dt><dd>{{ $customer->phone ?? '—' }}</dd>
                <dt class="text-ink/55">Paket</dt><dd>{{ $customer->package ?? '—' }}</dd>
                <dt class="text-ink/55">Rate Limit</dt><dd>{{ $customer->rate_limit ?? '—' }}</dd>
                <dt class="text-ink/55">Status</dt><dd>{{ $customer->status }}</dd>
                <dt class="text-ink/55">Joined</dt><dd>{{ optional($customer->joined_at)->format('d M Y') }}</dd>
            </dl>
        @else
            <p class="text-sm text-ink/55">Tidak ada profile pelanggan terkait username ini.</p>
        @endif
    </div>

    <div class="bg-white rounded-xl border border-cream-deep/60 p-5">
        <div class="font-semibold mb-3">RADIUS Attributes (radcheck)</div>
        <table class="text-sm w-full">
            <thead class="text-ink/55"><tr><th class="text-left">Attribute</th><th class="text-left">Op</th><th class="text-left">Value</th></tr></thead>
            <tbody>
                @forelse($check as $row)
                    <tr class="border-t border-cream-deep/60"><td>{{ $row->attribute }}</td><td>{{ $row->op }}</td><td class="font-mono">{{ $row->value }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-ink/45 py-2">Tidak ada atribut.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white rounded-xl border border-cream-deep/60 p-5 mt-4">
    <div class="font-semibold mb-3">Sesi Terakhir (radacct)</div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="text-ink/55">
                <tr><th class="text-left">Start</th><th class="text-left">Stop</th><th class="text-left">NAS IP</th><th class="text-left">Framed IP</th><th class="text-right">In</th><th class="text-right">Out</th></tr>
            </thead>
            <tbody>
                @forelse($sessions as $s)
                    <tr class="border-t border-cream-deep/60">
                        <td>{{ optional($s->acctstarttime)->format('Y-m-d H:i') }}</td>
                        <td>{{ optional($s->acctstoptime)->format('Y-m-d H:i') ?? '—' }}</td>
                        <td>{{ $s->nasipaddress }}</td>
                        <td>{{ $s->framedipaddress }}</td>
                        <td class="text-right">{{ number_format($s->acctinputoctets ?? 0) }}</td>
                        <td class="text-right">{{ number_format($s->acctoutputoctets ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-ink/45 py-2 text-center">Belum ada sesi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
