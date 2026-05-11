@extends('layouts.app')
@section('title','Pengaturan — Tenants')
@section('breadcrumb','Pengaturan / Tenants')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Tenants</h1>
        <p class="text-sm text-ink/55">Kelola penyewa portal — tiap tenant adalah ISP company terpisah dengan datanya sendiri.</p>
    </div>
    <div class="flex items-center gap-2 self-start sm:self-auto">
        <a href="{{ route('settings.tenants.create') }}" class="px-4 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm whitespace-nowrap">+ Tambah Tenant</a>
        <a href="{{ route('settings.tenants.delete-form') }}" class="px-4 py-2 bg-rose-100 text-rose-900 border border-rose-200 rounded-xl shadow-card hover:bg-rose-200 text-sm whitespace-nowrap">Hapus Tenant</a>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm whitespace-pre-wrap">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">Total Tenant</div>
        <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">Aktif</div>
        <div class="text-2xl font-bold text-emerald-700">{{ $stats['active'] }}</div>
    </div>
    <div class="bg-white rounded-2xl shadow-card p-4">
        <div class="text-xs text-ink/55">Nonaktif</div>
        <div class="text-2xl font-bold text-rose-600">{{ $stats['inactive'] }}</div>
    </div>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ $qs }}" placeholder="Cari nama / code / email" class="flex-1 min-w-[200px] sm:max-w-md border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
    <button class="px-4 py-2 bg-ink text-white rounded-xl text-sm">Filter</button>
    @if($qs)
        <a href="{{ route('settings.tenants.index') }}" class="px-4 py-2 rounded-xl text-sm text-ink/60 hover:bg-cream-deep/60">Reset</a>
    @endif
</form>

<div class="bg-white rounded-3xl shadow-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-deep/60 text-ink/70">
            <tr>
                <th class="text-left px-5 py-3">Code</th>
                <th class="text-left px-5 py-3">Nama</th>
                <th class="text-left px-5 py-3">Plan</th>
                <th class="text-left px-5 py-3">Pelanggan</th>
                <th class="text-left px-5 py-3">User</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-right px-5 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $t)
                <tr class="border-t border-ink/5">
                    <td class="px-5 py-3 font-mono text-xs">{{ $t->code }}</td>
                    <td class="px-5 py-3 font-medium">
                        {{ $t->name }}
                        @if($t->contact_email)
                            <div class="text-xs text-ink/50">{{ $t->contact_email }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-ink/5 text-ink/70 capitalize">{{ $t->plan }}</span>
                        @if($t->max_customers)
                            <div class="text-[11px] text-ink/45 mt-0.5">cap {{ number_format($t->max_customers, 0, ',', '.') }}</div>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-ink/70">{{ $t->customers_count }}</td>
                    <td class="px-5 py-3 text-ink/70">{{ $t->users_count }}</td>
                    <td class="px-5 py-3">
                        @if($t->is_active)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-900">Aktif</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-900">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-1.5">
                            <form method="POST" action="{{ route('settings.tenants.switch', $t) }}" class="inline">
                                @csrf
                                <button class="px-2.5 py-1 rounded-lg text-xs bg-cream-deep/60 hover:bg-cream-deep text-ink font-medium" title="Lihat dashboard sebagai tenant ini">View as</button>
                            </form>
                            <a href="{{ route('settings.tenants.edit', $t) }}" class="px-2.5 py-1 rounded-lg text-xs bg-ink/5 hover:bg-ink/10 text-ink/80">Edit</a>
                            @if($t->code !== \App\Models\Tenant::DEFAULT_CODE)
                                <form method="POST" action="{{ route('settings.tenants.regenerate-admin-password', $t) }}" class="inline" onsubmit="return confirm('Regenerate password admin untuk tenant {{ $t->name }}? Password lama akan diganti.')">
                                    @csrf
                                    <input type="hidden" name="send_wa" value="1">
                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs bg-amber-100 hover:bg-amber-200 text-amber-900 font-medium" title="Generate password baru + kirim via WA ke PIC (kalau contact_phone diisi)">
                                        Regen Pass + WA
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('settings.tenants.regenerate-admin-password', $t) }}" class="inline" onsubmit="return confirm('Regenerate password admin untuk tenant {{ $t->name }}? Password lama akan diganti. Lo akan copy paste manual ke pelanggan.')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1 rounded-lg text-xs bg-amber-50 hover:bg-amber-100 text-amber-800" title="Generate password baru, tampil sekali di success message (gak kirim WA)">
                                        Regen Manual
                                    </button>
                                </form>
                            @endif
                            @if($t->code !== \App\Models\Tenant::DEFAULT_CODE)
                                <form method="POST" action="{{ route('settings.tenants.destroy', $t) }}" class="inline" onsubmit="return confirm('Hapus tenant {{ $t->name }}? Hanya bisa kalau gak ada data pelanggan/invoice/user.')">
                                    @csrf @method('DELETE')
                                    <button class="px-2.5 py-1 rounded-lg text-xs bg-rose-100 hover:bg-rose-200 text-rose-900">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center py-8 text-ink/50">Belum ada tenant.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $rows->links() }}
</div>
@endsection
