@extends('layouts.app')
@section('title','Buat PPPoE User')

@section('content')
<h1 class="text-xl font-bold mb-4">Buat User PPPoE</h1>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('pppoe.store') }}" class="bg-white p-6 rounded-xl border border-slate-100 space-y-4">
        @csrf
        <div class="font-semibold">Single Create</div>
        <div>
            <label class="text-sm font-medium">Nama Lengkap</label>
            <input name="full_name" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Username (kosongkan = auto generate)</label>
                <input name="username" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 font-mono">
                <p class="text-xs text-slate-500 mt-1">Format auto: <code>{{ config('ahnet.radius.pppoe_username_prefix') }}nama#XX</code></p>
            </div>
            <div>
                <label class="text-sm font-medium">Password (kosongkan = auto)</label>
                <input name="password" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2 font-mono">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Group RADIUS</label>
                <input name="group" placeholder="{{ config('ahnet.radius.default_pppoe_group') }}" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">Mikrotik-Rate-Limit</label>
                <input name="rate_limit" placeholder="10M/10M" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Simultaneous-Use</label>
                <input name="simultaneous_use" type="number" min="1" max="10" value="1" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">Mikrotik Device (opsional)</label>
                <select name="mikrotik_device_id" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
                    <option value="">— tanpa sync —</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}">{{ $d->name }} ({{ $d->host }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Mikrotik Profile</label>
                <input name="mikrotik_profile" placeholder="default" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
            </div>
            <label class="flex items-center gap-2 mt-7 text-sm">
                <input type="checkbox" name="sync_to_mikrotik" value="1"> Push langsung ke Mikrotik (/ppp/secret)
            </label>
        </div>
        <button class="px-4 py-2 bg-[#0f4d8a] text-white rounded-lg">Simpan</button>
    </form>

    <form method="POST" action="{{ route('pppoe.bulk') }}" class="bg-white p-6 rounded-xl border border-slate-100 space-y-4">
        @csrf
        <div class="font-semibold">Bulk Create</div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Jumlah</label>
                <input name="count" type="number" min="1" max="1000" value="10" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">Name seed</label>
                <input name="name_seed" required value="user" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
            </div>
        </div>
        <div>
            <label class="text-sm font-medium">Group RADIUS</label>
            <input name="group" placeholder="{{ config('ahnet.radius.default_pppoe_group') }}" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Mikrotik-Rate-Limit</label>
            <input name="rate_limit" placeholder="10M/10M" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg">Generate Bulk</button>
    </form>
</div>
@endsection
