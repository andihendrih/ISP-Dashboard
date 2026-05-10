@extends('layouts.app')
@section('title','Edit Tenant')
@section('breadcrumb','Pengaturan / Tenants / Edit')

@section('content')
<div class="mb-4">
    <h1 class="text-xl sm:text-2xl font-bold">Edit Tenant: {{ $tenant->name }}</h1>
    <p class="text-sm text-ink/55">Code: <span class="font-mono">{{ $tenant->code }}</span></p>
</div>

@if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('settings.tenants.update', $tenant) }}" class="space-y-5 max-w-3xl">
    @csrf @method('PUT')

    <div class="bg-white rounded-3xl shadow-card p-5 space-y-4">
        <div class="text-xs uppercase tracking-widest text-ink/45 font-semibold">Identitas Tenant</div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-1">
                <label class="text-xs text-ink/55 font-medium">Code</label>
                <input name="code" required value="{{ old('code', $tenant->code) }}" pattern="[a-z0-9_\-]{3,16}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
                <div class="text-[11px] text-rose-700 mt-1">⚠️ Hati-hati ubah code — dipakai sebagai prefix RADIUS username.</div>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs text-ink/55 font-medium">Nama</label>
                <input name="name" required value="{{ old('name', $tenant->name) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-ink/55 font-medium">Plan</label>
                <select name="plan" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
                    @foreach($plans as $p)
                        <option value="{{ $p }}" @selected(old('plan', $tenant->plan)===$p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Max Pelanggan</label>
                <input name="max_customers" type="number" min="1" value="{{ old('max_customers', $tenant->max_customers) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 mt-1">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $tenant->is_active)) class="rounded border-ink/30">
                    <span class="text-sm">Aktif</span>
                </label>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-card p-5 space-y-4">
        <div class="text-xs uppercase tracking-widest text-ink/45 font-semibold">Info Brand</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="text-xs text-ink/55 font-medium">Nama Perusahaan</label>
                <input name="brand_company" value="{{ old('brand_company', $tenant->brand_company) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Phone</label>
                <input name="brand_phone" value="{{ old('brand_phone', $tenant->brand_phone) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Email</label>
                <input name="brand_email" type="email" value="{{ old('brand_email', $tenant->brand_email) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Alamat</label>
                <input name="brand_address" value="{{ old('brand_address', $tenant->brand_address) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-card p-5 space-y-4">
        <div class="text-xs uppercase tracking-widest text-ink/45 font-semibold">PIC Tenant</div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-ink/55 font-medium">Nama Kontak</label>
                <input name="contact_name" value="{{ old('contact_name', $tenant->contact_name) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Phone</label>
                <input name="contact_phone" value="{{ old('contact_phone', $tenant->contact_phone) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Email</label>
                <input name="contact_email" type="email" value="{{ old('contact_email', $tenant->contact_email) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
        <div>
            <label class="text-xs text-ink/55 font-medium">Catatan</label>
            <textarea name="notes" rows="2" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">{{ old('notes', $tenant->notes) }}</textarea>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button class="px-5 py-2.5 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm font-semibold">Simpan</button>
        <a href="{{ route('settings.tenants.index') }}" class="text-sm text-ink/60 hover:text-ink">Batal</a>
    </div>
</form>
@endsection
