@extends('layouts.app')
@section('title','Mikrotik Devices')

@section('content')
<h1 class="text-xl font-bold mb-4">Mikrotik Devices</h1>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
    @forelse($devices as $d)
        <div class="bg-white p-5 rounded-xl border border-slate-100">
            <div class="font-semibold">{{ $d->name }}</div>
            <div class="text-xs text-slate-500 font-mono">{{ $d->host }}:{{ $d->api_port }}</div>
            <div class="text-xs mt-2 space-y-0.5 text-slate-600">
                <div>Identity: <span class="font-mono">{{ $d->identity ?? '—' }}</span></div>
                <div>Board: {{ $d->board_name ?? '—' }}</div>
                <div>Version: {{ $d->version ?? '—' }}</div>
                <div>Last seen: {{ optional($d->last_seen_at)->diffForHumans() ?? 'never' }}</div>
            </div>
            <a href="{{ route('mikrotik.show', $d->id) }}" class="inline-block mt-3 text-sm text-[#0f4d8a] hover:underline">Buka</a>
        </div>
    @empty
        <div class="text-slate-500">Belum ada device terdaftar.</div>
    @endforelse
</div>

<form method="POST" action="{{ route('mikrotik.store') }}" class="bg-white p-6 rounded-xl border border-slate-100 max-w-2xl space-y-3">
    @csrf
    <div class="font-semibold">Tambah Device</div>
    <div class="grid grid-cols-2 gap-3">
        <input name="name"     placeholder="Nama"            class="border border-slate-300 rounded-lg px-3 py-2" required>
        <input name="host"     placeholder="Host / IP"        class="border border-slate-300 rounded-lg px-3 py-2 font-mono" required>
        <input name="api_port" placeholder="API Port (8728)"  class="border border-slate-300 rounded-lg px-3 py-2" type="number">
        <input name="username" placeholder="Username"         class="border border-slate-300 rounded-lg px-3 py-2" required>
        <input name="password" placeholder="Password"         class="border border-slate-300 rounded-lg px-3 py-2" type="password" required>
        <input name="snmp_community" placeholder="SNMP community" class="border border-slate-300 rounded-lg px-3 py-2">
    </div>
    <label class="text-sm flex items-center gap-2"><input type="checkbox" name="use_ssl" value="1"> Gunakan SSL</label>
    <button class="px-4 py-2 bg-[#0f4d8a] text-white rounded-lg">Tambah</button>
</form>
@endsection
