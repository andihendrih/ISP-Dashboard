@extends('layouts.app')
@section('title','Edit Perangkat')
@section('breadcrumb','Inventory / Edit')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <h1 class="text-xl sm:text-2xl font-bold">Edit Perangkat</h1>
    <a href="{{ route('devices.show', $device) }}" class="text-sm text-ink/55 hover:underline">&larr; Kembali</a>
</div>

@if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">
        <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('devices.update', $device) }}" class="bg-white rounded-2xl border border-cream-deep/60 shadow-sm p-5 max-w-3xl">
    @csrf
    @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs font-semibold text-ink/70 mb-1">Tipe <span class="text-rose-500">*</span></label>
            <select name="type" required class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
                @foreach(['onu'=>'ONU','router'=>'Router','switch'=>'Switch','ap'=>'Access Point','radio'=>'Radio / PtP','cable'=>'Cable','other'=>'Lainnya'] as $k => $v)
                    <option value="{{ $k }}" @selected(old('type', $device->type) === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/70 mb-1">Brand</label>
            <input type="text" name="brand" value="{{ old('brand', $device->brand) }}"
                   class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/70 mb-1">Model</label>
            <input type="text" name="model" value="{{ old('model', $device->model) }}"
                   class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/70 mb-1">Serial Number <span class="text-rose-500">*</span></label>
            <input type="text" name="serial_number" value="{{ old('serial_number', $device->serial_number) }}" required
                   class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 font-mono bg-white">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/70 mb-1">MAC Address</label>
            <input type="text" name="mac_address" value="{{ old('mac_address', $device->mac_address) }}"
                   class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 font-mono bg-white">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/70 mb-1">Harga Beli (Rp)</label>
            <input type="number" step="0.01" name="purchase_price" value="{{ old('purchase_price', $device->purchase_price) }}"
                   class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/70 mb-1">Tanggal Beli</label>
            <input type="date" name="purchased_at" value="{{ old('purchased_at', $device->purchased_at?->format('Y-m-d')) }}"
                   class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/70 mb-1">Lokasi Gudang</label>
            <input type="text" name="warehouse_location" value="{{ old('warehouse_location', $device->warehouse_location) }}"
                   class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-xs font-semibold text-ink/70 mb-1">Catatan</label>
            <textarea name="notes" rows="3" class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">{{ old('notes', $device->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-5 flex gap-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl hover:bg-black text-sm">Simpan Perubahan</button>
        <a href="{{ route('devices.show', $device) }}" class="px-5 py-2 border border-ink/10 rounded-xl text-sm">Batal</a>
    </div>
</form>
@endsection
