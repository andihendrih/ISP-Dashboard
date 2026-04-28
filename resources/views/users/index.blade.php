@extends('layouts.app')
@section('title','User RADIUS')

@section('content')
<h1 class="text-xl font-bold mb-1">User RADIUS</h1>
<p class="text-sm text-slate-500 mb-4">Daftar pelanggan terhubung ke RADIUS (PPPoE & Hotspot).</p>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ $search }}" placeholder="Cari nama / username" class="border-slate-300 rounded-lg text-sm px-3 py-2">
    <select name="type" class="border-slate-300 rounded-lg text-sm">
        <option value="all"     @selected($type==='all')>Semua</option>
        <option value="pppoe"   @selected($type==='pppoe')>PPPoE</option>
        <option value="hotspot" @selected($type==='hotspot')>Hotspot</option>
    </select>
    <button class="px-3 py-1.5 bg-slate-700 text-white rounded-lg text-sm">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-left px-4 py-3">Code</th>
                <th class="text-left px-4 py-3">Nama</th>
                <th class="text-left px-4 py-3">Username</th>
                <th class="text-left px-4 py-3">Tipe</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Online</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2 font-mono">{{ $r->customer_code }}</td>
                    <td class="px-4 py-2">{{ $r->full_name }}</td>
                    <td class="px-4 py-2 font-mono">
                        @if($r->radius_username)
                            <a class="text-blue-600 hover:underline" href="{{ route('users.show', $r->radius_username) }}">{{ $r->radius_username }}</a>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 uppercase text-xs">{{ $r->service_type }}</td>
                    <td class="px-4 py-2 text-xs">{{ $r->status }}</td>
                    <td class="px-4 py-2">
                        @if($r->radius_username && isset($onlineSet[$r->radius_username]))
                            <span class="text-emerald-600">● online</span>
                        @else
                            <span class="text-slate-400">offline</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100">{{ $rows->links() }}</div>
</div>
@endsection
