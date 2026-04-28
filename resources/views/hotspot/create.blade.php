@extends('layouts.app')
@section('title','Generate Voucher Hotspot')

@section('content')
<h1 class="text-xl font-bold mb-4">Generate Voucher Hotspot</h1>
<form method="POST" action="{{ route('hotspot.store') }}" class="bg-white p-6 rounded-xl border border-slate-100 max-w-2xl space-y-4">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-sm font-medium">Jumlah Voucher</label>
            <input name="count" type="number" min="1" max="5000" value="50" required class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Panjang Code (huruf)</label>
            <input name="length" type="number" min="4" max="16" value="5" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
            <p class="text-xs text-slate-500 mt-1">Default 5 huruf (sesuai spesifikasi).</p>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="text-sm font-medium">Profile / Group</label>
            <input name="profile" placeholder="{{ config('ahnet.radius.default_hotspot_group') }}" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Rate Limit</label>
            <input name="rate_limit" placeholder="2M/2M" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
        </div>
    </div>
    <div>
        <label class="text-sm font-medium">Session-Timeout (menit, opsional)</label>
        <input name="valid_minutes" type="number" min="1" placeholder="1440" class="w-full mt-1 border border-slate-300 rounded-lg px-3 py-2">
    </div>
    <button class="px-4 py-2 bg-[#0f4d8a] text-white rounded-lg">Generate</button>
</form>
@endsection
