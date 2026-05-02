@extends('layouts.app')
@section('title','Tambah Pelanggan')
@section('breadcrumb','Pelanggan / Baru')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-2xl font-bold">Tambah Pelanggan</h1>
        <p class="text-sm text-ink/55">Daftarkan pelanggan baru. Auto-billing akan jalan tiap tanggal 1 jika "auto invoice" dicentang & paket sudah dipilih.</p>
    </div>
    <a href="{{ route('customers.index') }}" class="px-4 py-2 bg-white border border-ink/10 rounded-xl">← Kembali</a>
</div>

@if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-4 mb-4">
        <div class="font-semibold mb-1">Periksa kembali isian:</div>
        <ul class="list-disc list-inside text-sm">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('customers.store') }}" class="bg-white rounded-3xl shadow-card p-6 max-w-4xl space-y-5">
    @csrf

    {{-- Identitas --}}
    <div>
        <h3 class="text-sm font-bold text-ink/70 mb-3 uppercase">Identitas</h3>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Kode Pelanggan</label>
                <input name="customer_code" value="{{ old('customer_code', $suggestCode) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                <p class="text-xs text-ink/45 mt-1">Otomatis ter-generate. Bisa diganti manual jika perlu.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input name="full_name" required value="{{ old('full_name') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">No. Telp / WA</label>
                <input name="phone" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">Email</label>
                <input name="email" type="email" value="{{ old('email') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="text-sm font-medium">No. KTP / NIK</label>
                <input name="id_card_number" value="{{ old('id_card_number') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            </div>
            <div></div>
            <div class="col-span-2">
                <label class="text-sm font-medium">Alamat</label>
                <textarea name="address" rows="2" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">{{ old('address') }}</textarea>
            </div>
            <div>
                <label class="text-sm font-medium">Latitude</label>
                <input name="latitude" type="number" step="0.0000001" value="{{ old('latitude') }}" placeholder="-6.200000" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
            </div>
            <div>
                <label class="text-sm font-medium">Longitude</label>
                <input name="longitude" type="number" step="0.0000001" value="{{ old('longitude') }}" placeholder="106.816666" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
            </div>
        </div>
    </div>

    {{-- Layanan --}}
    <div class="border-t border-cream-deep/60 pt-5">
        <h3 class="text-sm font-bold text-ink/70 mb-3 uppercase">Layanan</h3>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Tipe Layanan <span class="text-rose-500">*</span></label>
                <select name="service_type" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                    <option value="pppoe"   @selected(old('service_type', 'pppoe') === 'pppoe')>PPPoE</option>
                    <option value="hotspot" @selected(old('service_type') === 'hotspot')>Hotspot</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Status <span class="text-rose-500">*</span></label>
                <select name="status" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                    @foreach(['pending','active','free','isolir','inactive'] as $s)
                        <option value="{{ $s }}" @selected(old('status', 'pending') === $s)>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Username RADIUS</label>
                <input name="radius_username" value="{{ old('radius_username') }}" placeholder="ahnet_username" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                <p class="text-xs text-ink/45 mt-1">Kosongkan dulu kalau user RADIUS belum dibuat. Bikin lewat menu <a href="{{ route('pppoe.create') }}" class="underline">PPPoE → Buat Baru</a>.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Mikrotik Device</label>
                <select name="mikrotik_device_id" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                    <option value="">— Tidak di-set —</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}" @selected((int) old('mikrotik_device_id') === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Paket / Group RADIUS</label>
                <input name="package" value="{{ old('package') }}" placeholder="pppoe-default" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                <p class="text-xs text-ink/45 mt-1">Auto-isi dari paket layanan jika dikosongkan.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Rate Limit</label>
                <input name="rate_limit" value="{{ old('rate_limit') }}" placeholder="10M/10M" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
            </div>
            <div>
                <label class="text-sm font-medium">Tgl Pasang</label>
                <input name="joined_at" type="date" value="{{ old('joined_at', now()->toDateString()) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                <p class="text-xs text-ink/45 mt-1">Kalau bukan tanggal 1 → invoice pertama otomatis di-prorate.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Berlaku Sampai</label>
                <input name="expired_at" type="date" value="{{ old('expired_at') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            </div>
        </div>
    </div>

    {{-- Billing --}}
    <div class="border-t border-cream-deep/60 pt-5">
        <h3 class="text-sm font-bold text-ink/70 mb-3 uppercase">Billing</h3>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="text-sm font-medium">Paket Layanan (Harga)</label>
                <select name="service_plan_id" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                    <option value="">— Belum di-set —</option>
                    @foreach($plans as $p)
                        <option value="{{ $p->id }}" data-rate="{{ $p->rate_limit }}" data-group="{{ $p->radius_group }}" @selected((int) old('service_plan_id') === $p->id)>
                            {{ $p->name }} — Rp {{ number_format($p->price, 0, ',', '.') }} / bulan
                        </option>
                    @endforeach
                </select>
                @if($plans->isEmpty())
                    <p class="text-xs text-amber-700 mt-1">Belum ada paket. <a href="{{ route('plans.create') }}" class="underline">Buat paket dulu</a>.</p>
                @endif
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 mt-1">
                    <input type="hidden" name="billing_enabled" value="0">
                    <input type="checkbox" name="billing_enabled" value="1" @checked(old('billing_enabled', '1')) class="rounded border-ink/20">
                    <span class="text-sm">Generate invoice otomatis tiap bulan</span>
                </label>
            </div>
            <div class="col-span-2">
                <label class="text-sm font-medium">Catatan Internal</label>
                <textarea name="notes" rows="2" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">{{ old('notes') }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex gap-2 pt-3">
        <button class="px-5 py-2.5 bg-ink text-white rounded-xl hover:bg-black font-semibold">Simpan Pelanggan</button>
        <a href="{{ route('customers.index') }}" class="px-5 py-2.5 rounded-xl text-ink/60 hover:bg-cream-deep/60">Batal</a>
    </div>
</form>

<script>
// Auto-fill rate_limit + package dari paket dipilih (kalau field masih kosong)
document.querySelector('select[name="service_plan_id"]').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const rate = opt.getAttribute('data-rate');
    const group = opt.getAttribute('data-group');
    const rateInput = document.querySelector('input[name="rate_limit"]');
    const packageInput = document.querySelector('input[name="package"]');
    if (rate && !rateInput.value) rateInput.value = rate;
    if (group && !packageInput.value) packageInput.value = group;
});
</script>
@endsection
