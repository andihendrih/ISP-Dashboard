@extends('layouts.app')
@section('title','Hotspot Vouchers')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold">Hotspot Vouchers</h1>
        <p class="text-sm text-ink/55">Voucher hotspot terdaftar di FreeRADIUS (username = password).</p>
    </div>
    <a href="{{ route('hotspot.create') }}" class="px-4 py-2 bg-ink text-white rounded-lg text-sm">+ Generate Voucher</a>
</div>

<form method="GET" class="mb-4 flex gap-2">
    <input name="batch_id" value="{{ request('batch_id') }}" placeholder="Filter Batch ID" class="border-ink/10 rounded-lg text-sm">
    <select name="status" class="border-ink/10 rounded-lg text-sm">
        <option value="">— Status —</option>
        <option value="unused"  @selected(request('status')==='unused')>Unused</option>
        <option value="used"    @selected(request('status')==='used')>Used</option>
        <option value="expired" @selected(request('status')==='expired')>Expired</option>
    </select>
    <button class="px-3 py-1.5 bg-slate-700 text-white rounded-lg text-sm">Filter</button>
</form>

<div class="bg-white rounded-xl border border-cream-deep/60 shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-card text-ink/65">
            <tr>
                <th class="text-left px-4 py-3">Code</th>
                <th class="text-left px-4 py-3">Batch</th>
                <th class="text-left px-4 py-3">Profile</th>
                <th class="text-left px-4 py-3">Rate Limit</th>
                <th class="text-left px-4 py-3">Valid (mnt)</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $v)
                <tr class="border-t border-cream-deep/60">
                    <td class="px-4 py-2 font-mono">{{ $v->code }}</td>
                    <td class="px-4 py-2 text-xs">{{ $v->batch_id }}</td>
                    <td class="px-4 py-2">{{ $v->profile }}</td>
                    <td class="px-4 py-2">{{ $v->rate_limit }}</td>
                    <td class="px-4 py-2">{{ $v->valid_minutes }}</td>
                    <td class="px-4 py-2"><span class="px-2 py-0.5 text-xs rounded-full bg-cream-deep/60">{{ $v->status }}</span></td>
                    <td class="px-4 py-2">
                        <form method="POST" action="{{ route('hotspot.destroy', $v->id) }}" onsubmit="return confirm('Hapus voucher?')">
                            @csrf @method('DELETE')
                            <button class="text-rose-600 text-xs hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-ink/45">Belum ada voucher.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-cream-deep/60">{{ $rows->links() }}</div>
</div>
@endsection
