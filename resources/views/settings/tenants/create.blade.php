@extends('layouts.app')
@section('title','Tambah Tenant')
@section('breadcrumb','Pengaturan / Tenants / Tambah')

@section('content')
<div class="mb-4">
    <h1 class="text-xl sm:text-2xl font-bold">Tambah Tenant</h1>
    <p class="text-sm text-ink/55">Tenant adalah ISP company yang nyewa portal lo. Akan otomatis dibuat 1 admin awal supaya bisa login.</p>
</div>

@if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('settings.tenants.store') }}" class="space-y-5 max-w-3xl">
    @csrf

    <div class="bg-white rounded-3xl shadow-card p-5 space-y-4">
        <div class="text-xs uppercase tracking-widest text-ink/45 font-semibold">Identitas Tenant</div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-1">
                <label class="text-xs text-ink/55 font-medium">Code <span class="text-rose-600">*</span></label>
                <input name="code" required value="{{ old('code') }}" pattern="[a-z0-9_\-]{3,16}" placeholder="padi, langit, dst" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
                <div class="text-[11px] text-ink/45 mt-1">3-16 karakter, lowercase, dipakai sebagai prefix RADIUS username.</div>
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs text-ink/55 font-medium">Nama Tenant <span class="text-rose-600">*</span></label>
                <input name="name" required value="{{ old('name') }}" placeholder="Padi Net, Langit ISP, ..." class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-ink/55 font-medium">Plan <span class="text-rose-600">*</span></label>
                <select name="plan" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
                    @foreach($plans as $p)
                        <option value="{{ $p }}" @selected(old('plan')===$p)>{{ ucfirst($p) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Max Pelanggan</label>
                <input name="max_customers" type="number" min="1" value="{{ old('max_customers') }}" placeholder="kosongin untuk unlimited" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 mt-1">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1')) class="rounded border-ink/30">
                    <span class="text-sm">Aktif</span>
                </label>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-card p-5 space-y-4">
        <div class="text-xs uppercase tracking-widest text-ink/45 font-semibold">Info Brand <span class="text-ink/40 font-normal">(muncul di invoice / portal pelanggan tenant)</span></div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="text-xs text-ink/55 font-medium">Nama Perusahaan</label>
                <input name="brand_company" value="{{ old('brand_company') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Phone</label>
                <input name="brand_phone" value="{{ old('brand_phone') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Email</label>
                <input name="brand_email" type="email" value="{{ old('brand_email') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Alamat</label>
                <input name="brand_address" value="{{ old('brand_address') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-card p-5 space-y-4">
        <div class="text-xs uppercase tracking-widest text-ink/45 font-semibold">PIC Tenant</div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-ink/55 font-medium">Nama Kontak</label>
                <input name="contact_name" value="{{ old('contact_name') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Phone</label>
                <input name="contact_phone" value="{{ old('contact_phone') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Email</label>
                <input name="contact_email" type="email" value="{{ old('contact_email') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>

        <div>
            <label class="text-xs text-ink/55 font-medium">Catatan</label>
            <textarea name="notes" rows="2" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-card p-5 space-y-4 border-2 border-accent/40">
        <div class="text-xs uppercase tracking-widest text-ink/45 font-semibold">Admin Awal Tenant</div>
        <p class="text-xs text-ink/55">User admin pertama untuk tenant ini. Mereka login pakai email & password ini, lalu bisa manage user/pelanggan tenantnya sendiri.</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label class="text-xs text-ink/55 font-medium">Nama <span class="text-rose-600">*</span></label>
                <input name="admin_name" required value="{{ old('admin_name') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="text-xs text-ink/55 font-medium">Email Login <span class="text-rose-600">*</span></label>
                <input name="admin_email" type="email" required value="{{ old('admin_email') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="text-xs text-ink/55 font-medium">Password <span class="text-ink/40 font-normal">(opsional — kosongin untuk auto-generate)</span></label>
                <input name="admin_password" type="text" placeholder="auto-generate kalau kosong" value="{{ old('admin_password') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 text-sm font-mono">
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <button class="px-5 py-2.5 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm font-semibold">Buat Tenant</button>
        <a href="{{ route('settings.tenants.index') }}" class="text-sm text-ink/60 hover:text-ink">Batal</a>
    </div>
</form>
@endsection
