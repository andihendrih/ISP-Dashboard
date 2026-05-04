@extends('layouts.app')
@section('title','Edit User')
@section('breadcrumb','Pengaturan / Pengguna / Edit')

@section('content')
<div class="mb-4">
    <h1 class="text-xl sm:text-2xl font-bold">Edit User</h1>
    <p class="text-sm text-ink/55">{{ $user->name }} — {{ $user->email }}</p>
</div>

@if($errors->any())
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
@endif
@if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

@if($isCustomer && $user->customerProfile)
    <div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl p-3 mb-4 text-sm">
        Akun ini terhubung ke pelanggan
        <a href="{{ route('customers.edit', $user->customerProfile->id) }}" class="font-semibold underline">{{ $user->customerProfile->customer_code }} — {{ $user->customerProfile->full_name }}</a>.
        Role customer tidak bisa diubah dari sini.
    </div>
@endif

<form method="POST" action="{{ route('settings.users.update', $user) }}" class="bg-white rounded-3xl shadow-card p-5 max-w-2xl space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label class="block text-xs text-ink/55 font-medium mb-1">Nama</label>
        <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2">
    </div>

    <div>
        <label class="block text-xs text-ink/55 font-medium mb-1">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2">
    </div>

    <div>
        <label class="block text-xs text-ink/55 font-medium mb-1">Role</label>
        <select name="role_id" required {{ $isCustomer ? 'disabled' : '' }} class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
            @foreach($roles as $r)
                <option value="{{ $r->id }}" @selected(old('role_id', $user->role_id) == $r->id)>{{ $r->label }} ({{ $r->name }})</option>
            @endforeach
        </select>
        @if($isCustomer)
            <input type="hidden" name="role_id" value="{{ $user->role_id }}">
        @endif
    </div>

    <label class="inline-flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
        <span class="text-sm">Aktif</span>
    </label>

    <div class="flex gap-2 pt-2">
        <button class="px-4 py-2 bg-ink text-white rounded-xl text-sm">Simpan</button>
        <a href="{{ route('settings.users.index') }}" class="px-4 py-2 rounded-xl text-sm text-ink/60 hover:bg-cream-deep/60">Batal</a>
    </div>
</form>

<div class="mt-4 max-w-2xl">
    <form method="POST" action="{{ route('settings.users.reset', $user) }}" onsubmit="return confirm('Reset password user ini? Password baru akan ditampilkan setelah simpan.')">
        @csrf
        <button class="px-4 py-2 bg-amber-100 text-amber-900 hover:bg-amber-200 rounded-xl text-sm">Reset Password</button>
    </form>
</div>
@endsection
