@extends('layouts.app')
@section('title','Radius')

@section('content')
<h1 class="text-xl font-bold mb-1">Radius</h1>
<p class="text-sm text-ink/55 mb-4">Daftar pelanggan terhubung ke RADIUS (PPPoE &amp; Hotspot).</p>

<form method="GET" class="mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ $search }}" placeholder="Cari nama / username" class="border-ink/10 rounded-lg text-sm px-3 py-2">
    <select name="type" class="border-ink/10 rounded-lg text-sm">
        <option value="all"     @selected($type==='all')>Semua</option>
        <option value="pppoe"   @selected($type==='pppoe')>PPPoE</option>
        <option value="hotspot" @selected($type==='hotspot')>Hotspot</option>
    </select>
    <button class="px-3 py-1.5 bg-slate-700 text-white rounded-lg text-sm">Filter</button>
</form>

<div class="bg-white rounded-xl border border-cream-deep/60 shadow-sm overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-cream-card text-ink/65">
            <tr>
                <th class="text-left px-4 py-3">Code</th>
                <th class="text-left px-4 py-3">Nama</th>
                <th class="text-left px-4 py-3">Username</th>
                <th class="text-left px-4 py-3">Password</th>
                <th class="text-left px-4 py-3">Tipe</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Online</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $r)
                <tr class="border-t border-cream-deep/60">
                    <td class="px-4 py-2 font-mono">{{ $r->customer_code }}</td>
                    <td class="px-4 py-2">{{ $r->full_name }}</td>
                    <td class="px-4 py-2 font-mono">
                        @if($r->radius_username)
                            <a class="text-blue-600 hover:underline" href="{{ route('users.show', $r->radius_username) }}">{{ $r->radius_username }}</a>
                        @else
                            <span class="text-ink/45">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 font-mono text-xs">
                        @if($r->radius_password)
                            <span class="pwd-mask" data-pwd="{{ $r->radius_password }}">••••••••</span>
                            <button type="button" class="pwd-toggle ml-1 text-ink/40 hover:text-ink" title="Tampilkan/sembunyikan">👁</button>
                        @else
                            <span class="text-ink/30">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 uppercase text-xs">{{ $r->service_type }}</td>
                    <td class="px-4 py-2 text-xs">{{ $r->status }}</td>
                    <td class="px-4 py-2">
                        @if($r->radius_username && isset($onlineSet[$r->radius_username]))
                            <span class="text-emerald-600">● online</span>
                        @else
                            <span class="text-ink/45">offline</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-ink/45">Belum ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-cream-deep/60">{{ $rows->links() }}</div>
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
