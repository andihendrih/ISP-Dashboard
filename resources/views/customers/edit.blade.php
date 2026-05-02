@extends('layouts.app')
@section('title','Edit Pelanggan')

@section('content')
<h1 class="text-xl font-bold mb-4">Edit Pelanggan: {{ $row->full_name }}</h1>

<form method="POST" action="{{ route('customers.update', $row->id) }}" class="bg-white p-6 rounded-xl border border-cream-deep/60 max-w-3xl space-y-4">
    @csrf @method('PUT')
    <div class="grid grid-cols-2 gap-3">
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
            <input name="package" value="{{ old('package', $row->package) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Rate Limit</label>
            <input name="rate_limit" value="{{ old('rate_limit', $row->rate_limit) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Mikrotik Device</label>
            <select name="mikrotik_device_id" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                <option value="">—</option>
                @foreach($devices as $d)
                    <option value="{{ $d->id }}" @selected($row->mikrotik_device_id === $d->id)>{{ $d->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-span-2">
            <label class="text-sm font-medium">Alamat</label>
            <textarea name="address" rows="2" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">{{ old('address', $row->address) }}</textarea>
        </div>
        <div>
            <label class="text-sm font-medium">Berlaku Sampai</label>
            <input name="expired_at" type="date" value="{{ old('expired_at', optional($row->expired_at)->toDateString()) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
    </div>
    <button class="px-4 py-2 bg-ink text-white rounded-lg">Update</button>
</form>
@endsection
