@extends('layouts.app')
@section('title','Pengaturan — Pengguna')
@section('breadcrumb','Pengaturan / Pengguna')

@section('content')
@php
    $roleBadges = [
        'superadmin' => 'bg-purple-100 text-purple-900',
        'admin'      => 'bg-emerald-100 text-emerald-900',
        'noc'        => 'bg-sky-100 text-sky-900',
        'finance'    => 'bg-amber-100 text-amber-900',
        'teknisi'    => 'bg-orange-100 text-orange-900',
        'customer'   => 'bg-ink/10 text-ink/70',
    ];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Pengguna</h1>
        <p class="text-sm text-ink/55">Kelola akun staff dan akun pelanggan.</p>
    </div>
    <a href="{{ route('settings.users.create') }}" class="px-4 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm whitespace-nowrap self-start sm:self-auto">+ Tambah User</a>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <a href="{{ route('settings.users.index') }}"
       class="bg-white rounded-2xl shadow-card p-4 hover:bg-cream-deep/40 transition {{ $scope==='all' ? 'ring-2 ring-accent' : '' }}">
        <div class="text-xs text-ink/55">Total User</div>
        <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
    </a>
    <a href="{{ route('settings.users.index', ['scope' => 'staff']) }}"
       class="bg-white rounded-2xl shadow-card p-4 hover:bg-cream-deep/40 transition {{ $scope==='staff' ? 'ring-2 ring-accent' : '' }}">
        <div class="text-xs text-ink/55">Staff</div>
        <div class="text-2xl font-bold">{{ $stats['staff'] }}</div>
    </a>
    <a href="{{ route('settings.users.index', ['scope' => 'customer']) }}"
       class="bg-white rounded-2xl shadow-card p-4 hover:bg-cream-deep/40 transition {{ $scope==='customer' ? 'ring-2 ring-accent' : '' }}">
        <div class="text-xs text-ink/55">Akun Pelanggan</div>
        <div class="text-2xl font-bold">{{ $stats['customer'] }}</div>
    </a>
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">Nonaktif</div>
        <div class="text-2xl font-bold text-rose-600">{{ $stats['inactive'] }}</div>
    </div>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input type="hidden" name="scope" value="{{ $scope }}">
    <input name="q" value="{{ $qs }}" placeholder="Cari nama / email" class="flex-1 min-w-[200px] sm:max-w-md border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
    <select name="role" class="border border-ink/10 rounded-xl text-sm bg-white px-3">
        <option value="">— Semua Role —</option>
        @foreach($roles as $r)
            <option value="{{ $r->name }}" @selected($roleFilter === $r->name)>{{ $r->label }}</option>
        @endforeach
    </select>
    <button class="px-4 py-2 bg-ink text-white rounded-xl text-sm">Filter</button>
    @if($qs || $roleFilter)
        <a href="{{ route('settings.users.index', ['scope' => $scope]) }}" class="px-4 py-2 rounded-xl text-sm text-ink/60 hover:bg-cream-deep/60">Reset</a>
    @endif
</form>

<div class="bg-white rounded-3xl shadow-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-deep/60 text-ink/70">
            <tr>
                <th class="text-left px-5 py-3">Nama</th>
                <th class="text-left px-5 py-3">Email</th>
                <th class="text-left px-5 py-3">Role</th>
                <th class="text-left px-5 py-3">Pelanggan</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-right px-5 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $u)
                <tr class="border-t border-ink/5">
                    <td class="px-5 py-3 font-medium">{{ $u->name }}</td>
                    <td class="px-5 py-3 text-ink/70">{{ $u->email }}</td>
                    <td class="px-5 py-3">
                        @php $rn = $u->role->name ?? 'unknown'; @endphp
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $roleBadges[$rn] ?? 'bg-ink/10 text-ink/60' }}">
                            {{ $u->role->label ?? '—' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-ink/70">
                        @if($u->customerProfile)
                            <a href="{{ route('customers.edit', $u->customerProfile->id) }}" class="text-sky-700 hover:underline">
                                {{ $u->customerProfile->customer_code }} — {{ $u->customerProfile->full_name }}
                            </a>
                        @else
                            <span class="text-ink/30">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @if($u->is_active)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-900">Aktif</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-900">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        @php
                            // Hide aksi edit/reset/toggle/hapus kalau target adalah superadmin
                            // dan operator bukan superadmin (tenant admin gak boleh modify
                            // superadmin sama sekali — lebih bersih dari pada show button trus 403).
                            $isProtected = $u->role && $u->role->name === \App\Models\Role::SUPERADMIN
                                && (!auth()->user()->role || auth()->user()->role->name !== \App\Models\Role::SUPERADMIN);
                        @endphp
                        @if($isProtected)
                            <span class="text-xs text-ink/40 italic">— protected —</span>
                        @else
                            <div class="inline-flex gap-1">
                                <a href="{{ route('settings.users.edit', $u) }}" class="px-3 py-1.5 rounded-lg text-xs bg-ink/10 hover:bg-ink/20">Edit</a>
                                <form method="POST" action="{{ route('settings.users.reset', $u) }}" onsubmit="return confirm('Reset password user ini?')">
                                    @csrf
                                    <button class="px-3 py-1.5 rounded-lg text-xs bg-amber-100 text-amber-900 hover:bg-amber-200">Reset Pwd</button>
                                </form>
                                <form method="POST" action="{{ route('settings.users.toggle', $u) }}">
                                    @csrf
                                    <button class="px-3 py-1.5 rounded-lg text-xs {{ $u->is_active ? 'bg-rose-100 text-rose-900 hover:bg-rose-200' : 'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' }}">
                                        {{ $u->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('settings.users.destroy', $u) }}" onsubmit="return confirm('Hapus user {{ $u->name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="px-3 py-1.5 rounded-lg text-xs bg-rose-600 text-white hover:bg-rose-700">Hapus</button>
                                </form>
                            </div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-5 py-10 text-center text-ink/40">Belum ada user.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $rows->links() }}</div>
@endsection
