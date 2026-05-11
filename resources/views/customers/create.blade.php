@extends('layouts.app')
@section('title','Tambah Pelanggan')
@section('breadcrumb','Pelanggan / Baru')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
@endpush

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Tambah Pelanggan</h1>
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

<form method="POST" action="{{ route('customers.store') }}" class="bg-white rounded-3xl shadow-card p-4 sm:p-6 max-w-4xl space-y-5">
    @csrf

    {{-- Identitas --}}
    <div>
        <h3 class="text-sm font-bold text-ink/70 mb-3 uppercase">Identitas</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                <input id="lat-input" name="latitude" type="number" step="0.0000001" value="{{ old('latitude') }}" placeholder="-6.200000" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
            </div>
            <div>
                <label class="text-sm font-medium">Longitude</label>
                <input id="lng-input" name="longitude" type="number" step="0.0000001" value="{{ old('longitude') }}" placeholder="106.816666" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
            </div>
            <div class="col-span-2">
                <div class="flex items-center justify-between">
                    <label class="text-sm font-medium">Pin di Peta <span class="text-ink/40 font-normal">(klik untuk set lokasi)</span></label>
                    <button type="button" id="btn-locate" class="text-xs px-2 py-1 bg-cream-deep/60 rounded-lg hover:bg-cream-deep">📍 Pakai lokasi saya</button>
                </div>
                <div id="pick-map" class="mt-2 rounded-xl border border-ink/10" style="height:280px;"></div>
            </div>
        </div>
    </div>

    {{-- Layanan --}}
    <div class="border-t border-cream-deep/60 pt-5">
        <h3 class="text-sm font-bold text-ink/70 mb-3 uppercase">Layanan</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
            <div class="min-w-0">
                <label class="text-sm font-medium">Username RADIUS</label>
                <div class="flex items-stretch mt-1 max-w-full">
                    <span class="px-2 sm:px-3 py-2 bg-cream-deep/70 border border-r-0 border-ink/10 rounded-l-lg font-mono text-xs sm:text-sm font-semibold whitespace-nowrap shrink-0 select-none">{{ $tenantPrefix }}</span>
                    <input name="radius_username_suffix" value="{{ old('radius_username_suffix') }}" placeholder="kosongkan = auto" class="flex-1 min-w-0 border border-ink/10 rounded-r-lg px-3 py-2 font-mono text-sm">
                </div>
                <p class="text-xs text-ink/45 mt-1">Username otomatis diawali <span class="font-mono font-semibold">{{ $tenantPrefix }}</span>. Lo cuma ngetik suffix (mis. <span class="font-mono">budisantoso42</span>). Kosongkan untuk auto-generate.</p>
            </div>
            <div class="min-w-0">
                <label class="text-sm font-medium">Password RADIUS</label>
                <div class="flex gap-2 mt-1 max-w-full">
                    <input name="radius_password" id="rpw" value="{{ old('radius_password') }}" placeholder="kosongkan = auto" class="flex-1 min-w-0 border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
                    <button type="button" onclick="genPwd()" class="px-3 py-2 bg-cream-deep/70 hover:bg-cream-deep rounded-lg text-sm shrink-0">🎲</button>
                </div>
                <p class="text-xs text-ink/45 mt-1">Random 10 karakter. Klik 🎲 untuk regenerate.</p>
            </div>
            <div class="col-span-2">
                <label class="inline-flex items-center gap-2 mt-1 bg-accent-soft/50 border border-accent/40 px-3 py-2 rounded-lg w-full">
                    <input type="hidden" name="auto_radius" value="0">
                    <input type="checkbox" name="auto_radius" value="1" @checked(old('auto_radius', '1')) class="rounded border-ink/20">
                    <span class="text-sm"><strong>Auto provision ke FreeRADIUS</strong> — username, password, group &amp; rate-limit langsung ditulis ke <span class="font-mono">radcheck</span>/<span class="font-mono">radusergroup</span>/<span class="font-mono">radreply</span> waktu disimpan. (Tidak push ke Mikrotik)</span>
                </label>
            </div>
            <div>
                <label class="text-sm font-medium">Mikrotik Device <span class="text-ink/40 font-normal">(opsional)</span></label>
                <select name="mikrotik_device_id" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                    <option value="">— Tidak di-set —</option>
                    @foreach($devices as $d)
                        <option value="{{ $d->id }}" @selected((int) old('mikrotik_device_id') === $d->id)>{{ $d->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-ink/45 mt-1">Cuma referensi; user TIDAK di-push ke Mikrotik dari form ini.</p>
            </div>
            <div></div>
            <div>
                <label class="text-sm font-medium">Paket / Group RADIUS</label>
                @php($groupCount = collect($radiusGroups ?? [])->flatten()->count())
                @if($groupCount > 0)
                    <select name="package" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                        <option value="">— Pilih grup RADIUS —</option>
                        @foreach(['home' => 'Home (PPPoE Residential)', 'broadband' => 'Broadband', 'bisnis' => 'Bisnis', 'hotspot' => 'Hotspot (Voucher)', 'other' => 'Lainnya'] as $key => $label)
                            @if(!empty($radiusGroups[$key]))
                                <optgroup label="{{ $label }}">
                                    @foreach($radiusGroups[$key] as $g)
                                        <option value="{{ $g }}" @selected(old('package') === $g)>{{ $g }}</option>
                                    @endforeach
                                </optgroup>
                            @endif
                        @endforeach
                    </select>
                    <p class="text-xs text-ink/45 mt-1">Daftar grup di-fetch dari <code>radgroupreply</code> FreeRADIUS. Auto-pilih dari paket layanan kalau dikosongkan.</p>
                @else
                    <input name="package" value="{{ old('package') }}" placeholder="Home_20M" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                    <p class="text-xs text-amber-700 mt-1">⚠ DB RADIUS belum konek — input grup manual. Konfig <code>RADIUS_DB_*</code> di .env supaya dropdown otomatis muncul.</p>
                @endif
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
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
    const packageInput = document.querySelector('[name="package"]'); // bisa <input> atau <select>
    if (rate && !rateInput.value) rateInput.value = rate;
    if (group && packageInput && !packageInput.value) packageInput.value = group;
});

// Generate random password (10 chars, letters + digits + safe symbols)
function genPwd() {
    const chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789@#$%';
    let p = '';
    for (let i = 0; i < 10; i++) p += chars.charAt(Math.floor(Math.random() * chars.length));
    document.getElementById('rpw').value = p;
}
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const latInput = document.getElementById('lat-input');
    const lngInput = document.getElementById('lng-input');
    if (!latInput || !lngInput || !document.getElementById('pick-map')) return;

    const initLat = parseFloat(latInput.value) || -6.200000;
    const initLng = parseFloat(lngInput.value) || 106.816666;

    const map = L.map('pick-map').setView([initLat, initLng], (latInput.value && lngInput.value) ? 16 : 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    let marker = null;
    function setPin(lat, lng) {
        if (marker) marker.setLatLng([lat, lng]); else marker = L.marker([lat, lng], {draggable:true}).addTo(map);
        marker.on('dragend', () => { const p = marker.getLatLng(); latInput.value = p.lat.toFixed(7); lngInput.value = p.lng.toFixed(7); });
        latInput.value = lat.toFixed(7);
        lngInput.value = lng.toFixed(7);
    }
    if (latInput.value && lngInput.value) setPin(initLat, initLng);

    map.on('click', (e) => setPin(e.latlng.lat, e.latlng.lng));

    document.getElementById('btn-locate').addEventListener('click', () => {
        if (!navigator.geolocation) { alert('Browser tidak support geolocation.'); return; }
        navigator.geolocation.getCurrentPosition(
            (pos) => { setPin(pos.coords.latitude, pos.coords.longitude); map.setView([pos.coords.latitude, pos.coords.longitude], 16); },
            (err) => alert('Gagal akses lokasi: ' + err.message)
        );
    });

    // Sync from manual input
    [latInput, lngInput].forEach(inp => inp.addEventListener('change', () => {
        const la = parseFloat(latInput.value), ln = parseFloat(lngInput.value);
        if (!isNaN(la) && !isNaN(ln)) { setPin(la, ln); map.setView([la, ln], 16); }
    }));
})();
</script>
@endsection
