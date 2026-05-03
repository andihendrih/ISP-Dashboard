@extends('layouts.app')
@section('title','Pelanggan')
@section('breadcrumb','Pelanggan')

@section('content')
@php
    $statusBadges = [
        'active'   => 'bg-emerald-100 text-emerald-900',
        'free'     => 'bg-sky-100 text-sky-900',
        'pending'  => 'bg-amber-100 text-amber-900',
        'isolir'   => 'bg-rose-100 text-rose-900',
        'inactive' => 'bg-ink/10 text-ink/60',
    ];
@endphp

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Pelanggan</h1>
        <p class="text-sm text-ink/55">Daftar pelanggan AHNet.</p>
    </div>
    <a href="{{ route('customers.create') }}" class="px-4 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm whitespace-nowrap self-start sm:self-auto">+ Tambah Pelanggan</a>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / kode / username" class="flex-1 min-w-[200px] sm:max-w-md border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
    <select name="status" class="border border-ink/10 rounded-xl text-sm bg-white px-3">
        <option value="">— Semua Status —</option>
        @foreach(['active','isolir','free','pending','inactive'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button class="px-4 py-2 bg-ink text-white rounded-xl text-sm">Filter</button>
    @if(request('q') || request('status'))
        <a href="{{ route('customers.index') }}" class="px-4 py-2 rounded-xl text-sm text-ink/60 hover:bg-cream-deep/60">Reset</a>
    @endif
</form>

<div class="bg-white rounded-3xl shadow-card overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-deep/60 text-ink/70">
            <tr>
                <th class="text-left px-5 py-3">Code</th>
                <th class="text-left px-5 py-3">Nama</th>
                <th class="text-left px-5 py-3">Kontak</th>
                <th class="text-left px-5 py-3">Tipe</th>
                <th class="text-left px-5 py-3">Paket</th>
                <th class="text-left px-5 py-3">Status</th>
                <th class="text-left px-5 py-3">Username</th>
                <th class="text-left px-5 py-3">Password</th>
                <th class="text-left px-5 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr class="border-t border-cream-deep/60 hover:bg-cream-card/40">
                    <td class="px-5 py-3 font-mono text-xs">{{ $r->customer_code }}</td>
                    <td class="px-5 py-3 font-semibold">{{ $r->full_name }}</td>
                    <td class="px-5 py-3 text-xs">
                        <div>{{ $r->phone ?? '—' }}</div>
                        <div class="text-ink/50">{{ $r->email ?? '' }}</div>
                    </td>
                    <td class="px-5 py-3 uppercase text-xs">{{ $r->service_type }}</td>
                    <td class="px-5 py-3 text-xs">{{ $r->package ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-block px-2 py-1 rounded-lg text-xs font-medium {{ $statusBadges[$r->status] ?? 'bg-ink/10' }}">{{ ucfirst($r->status) }}</span>
                    </td>
                    <td class="px-5 py-3 font-mono text-xs">{{ $r->radius_username ?? '—' }}</td>
                    <td class="px-5 py-3 font-mono text-xs">
                        @if($r->radius_password)
                            <span class="pwd-mask" data-pwd="{{ $r->radius_password }}">••••••••</span>
                            <button type="button" class="pwd-toggle ml-1 text-ink/40 hover:text-ink" title="Tampilkan/sembunyikan">👁</button>
                        @else
                            <span class="text-ink/30">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('customers.edit', $r->id) }}" class="text-ink/60 text-xs hover:text-ink">Edit</a>
                        <span class="text-ink/20">·</span>
                        <form method="POST" action="{{ route('customers.destroy', $r->id) }}" class="inline" onsubmit="return confirm('Hapus pelanggan {{ $r->customer_code }} — {{ addslashes($r->full_name) }}? Data RADIUS (radcheck/radreply/radusergroup/radacct/radpostauth) juga akan dihapus.');">
                            @csrf @method('DELETE')
                            <button class="text-rose-600 text-xs hover:text-rose-800">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-5 py-12 text-center text-ink/45">
                    Belum ada data pelanggan.
                    <a href="{{ route('customers.create') }}" class="text-ink underline ml-1">Tambah pelanggan pertama →</a>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    @if($rows->hasPages())
        <div class="px-5 py-3 border-t border-cream-deep/60">{{ $rows->links() }}</div>
    @endif
</div>

<script>
document.querySelectorAll('.pwd-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const span = btn.previousElementSibling;
        const pwd = span.getAttribute('data-pwd');
        if (span.textContent === '••••••••') {
            span.textContent = pwd;
            span.classList.add('bg-accent/30','px-1','rounded');
        } else {
            span.textContent = '••••••••';
            span.classList.remove('bg-accent/30','px-1','rounded');
        }
    });
});
</script>
@endsection
