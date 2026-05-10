@extends('layouts.app')
@section('title','Edit Pelanggan')

@section('content')
<h1 class="text-xl font-bold mb-4">Edit Pelanggan: {{ $row->full_name }}</h1>

@if($row->radius_username)
    {{-- hidden form for regenerate RADIUS password (must live outside main form) --}}
    <form id="ah-regen-form" method="POST" action="{{ route('customers.regenerate-radius', $row->id) }}" class="hidden">@csrf</form>
    <script>
        function ahRegenRadiusPw() {
            if (!confirm('Generate password RADIUS baru untuk {{ $row->radius_username }}? Password lama akan ke-replace permanent di RADIUS server.')) return;
            document.getElementById('ah-regen-form').submit();
        }
    </script>
@endif

<form method="POST" action="{{ route('customers.update', $row->id) }}" class="bg-white p-6 rounded-xl border border-cream-deep/60 max-w-3xl space-y-4">
    @csrf @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="text-sm font-medium">Nama Lengkap</label>
            <input name="full_name" required value="{{ old('full_name', $row->full_name) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Telp</label>
            <input name="phone" value="{{ old('phone', $row->phone) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Email</label>
            <input name="email" type="email" value="{{ old('email', $row->email) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Status</label>
            <select name="status" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                @foreach(['active','isolir','free','pending','inactive'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $row->status) === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium">Tipe Layanan</label>
            <select name="service_type" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                <option value="pppoe"   @selected(old('service_type', $row->service_type) === 'pppoe')>PPPoE</option>
                <option value="hotspot" @selected(old('service_type', $row->service_type) === 'hotspot')>Hotspot</option>
            </select>
        </div>
        <div>
            <label class="text-sm font-medium">Paket / Group</label>
            @php($groupCount = collect($radiusGroups ?? [])->flatten()->count())
            @if($groupCount > 0)
                <select name="package" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                    <option value="">— Tidak di-set —</option>
                    @foreach(['home' => 'Home', 'broadband' => 'Broadband', 'bisnis' => 'Bisnis', 'hotspot' => 'Hotspot (Voucher)', 'other' => 'Lainnya'] as $key => $label)
                        @if(!empty($radiusGroups[$key]))
                            <optgroup label="{{ $label }}">
                                @foreach($radiusGroups[$key] as $g)
                                    <option value="{{ $g }}" @selected(old('package', $row->package) === $g)>{{ $g }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
            @else
                <input name="package" value="{{ old('package', $row->package) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
            @endif
        </div>
        <div>
            <label class="text-sm font-medium">Rate Limit</label>
            <input name="rate_limit" value="{{ old('rate_limit', $row->rate_limit) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Username</label>
            <input name="radius_username" value="{{ old('radius_username', $row->radius_username) }}" placeholder="ahnet_username" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
        </div>
        <div>
            <div class="flex items-center justify-between">
                <label class="text-sm font-medium">Password</label>
                @if($row->radius_username)
                    <button type="button" onclick="ahRegenRadiusPw()"
                        class="text-[11px] font-semibold text-ink/60 hover:text-ink bg-cream-deep/60 px-2 py-1 rounded-lg flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0114-4M20 15a8 8 0 01-14 4"/></svg>
                        Regenerate
                    </button>
                @endif
            </div>
            <input name="radius_password" value="{{ old('radius_password', $row->radius_password) }}" placeholder="auto-generate kalau kosong" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
        </div>

        <div>
            <label class="text-sm font-medium">Mikrotik Device <span class="text-ink/40 font-normal">(opsional)</span></label>
            <select name="mikrotik_device_id" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                <option value="">—</option>
                @foreach($devices as $d)
                    <option value="{{ $d->id }}" @selected($row->mikrotik_device_id === $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div></div>
        <div class="col-span-2">
            <label class="text-sm font-medium">Alamat</label>
            <textarea name="address" rows="2" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">{{ old('address', $row->address) }}</textarea>
        </div>
        <div>
            <label class="text-sm font-medium">Berlaku Sampai</label>
            <input name="expired_at" type="date" value="{{ old('expired_at', optional($row->expired_at)->toDateString()) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>

        <div class="col-span-2 border-t border-cream-deep/60 pt-4 mt-2">
            <h3 class="text-sm font-bold text-ink/70 mb-2">Billing</h3>
        </div>
        <div>
            <label class="text-sm font-medium">Paket Layanan (Harga)</label>
            <select name="service_plan_id" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                <option value="">— Belum di-set —</option>
                @foreach($plans as $p)
                    <option value="{{ $p->id }}" @selected((int) old('service_plan_id', $row->service_plan_id) === $p->id)>
                        {{ $p->name }} — Rp {{ number_format($p->price, 0, ',', '.') }} / bulan
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-ink/45 mt-1">Pelanggan tanpa paket tidak akan ikut auto-generate invoice.</p>
        </div>
        <div>
            <label class="text-sm font-medium">Aktifkan Auto Invoice?</label>
            <div class="mt-3">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="billing_enabled" value="0">
                    <input type="checkbox" name="billing_enabled" value="1" @checked(old('billing_enabled', $row->billing_enabled)) class="rounded border-ink/20">
                    <span class="text-sm">Generate invoice otomatis tiap bulan</span>
                </label>
            </div>
        </div>
    </div>
    <button class="px-4 py-2 bg-ink text-white rounded-lg">Update</button>
</form>
@endsection
