@extends('layouts.app')
@section('title','Pelanggan')

@section('content')
<h1 class="text-xl font-bold mb-4">Pelanggan</h1>
<form method="GET" class="mb-4 flex gap-2">
    <input name="q" value="{{ request('q') }}" placeholder="Cari nama / kode / username" class="border-slate-300 rounded-lg text-sm px-3 py-2">
    <select name="status" class="border-slate-300 rounded-lg text-sm">
        <option value="">— Status —</option>
        @foreach(['active','isolir','free','pending','inactive'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button class="px-3 py-1.5 bg-slate-700 text-white rounded-lg text-sm">Filter</button>
</form>

<div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-left px-4 py-3">Code</th>
                <th class="text-left px-4 py-3">Nama</th>
                <th class="text-left px-4 py-3">Tipe</th>
                <th class="text-left px-4 py-3">Paket</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Username RADIUS</th>
                <th class="text-left px-4 py-3"></th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2 font-mono">{{ $r->customer_code }}</td>
                    <td class="px-4 py-2">{{ $r->full_name }}</td>
                    <td class="px-4 py-2 uppercase text-xs">{{ $r->service_type }}</td>
                    <td class="px-4 py-2">{{ $r->package ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $r->status }}</td>
                    <td class="px-4 py-2 font-mono">{{ $r->radius_username ?? '—' }}</td>
                    <td class="px-4 py-2"><a href="{{ route('customers.edit', $r->id) }}" class="text-blue-600 text-xs hover:underline">Edit</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada data pelanggan.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100">{{ $rows->links() }}</div>
</div>
@endsection
