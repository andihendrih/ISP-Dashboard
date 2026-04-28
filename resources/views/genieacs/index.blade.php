@extends('layouts.app')
@section('title','GenieACS')

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold">GenieACS Devices</h1>
    <form method="POST" action="{{ route('genieacs.sync') }}">
        @csrf
        <button class="px-4 py-2 bg-emerald-600 text-white rounded-lg text-sm">Sync dari NBI</button>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-100 overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr>
                <th class="text-left px-4 py-3">Device ID</th>
                <th class="text-left px-4 py-3">Serial</th>
                <th class="text-left px-4 py-3">Manufacturer</th>
                <th class="text-left px-4 py-3">Model</th>
                <th class="text-left px-4 py-3">SSID</th>
                <th class="text-left px-4 py-3">IP</th>
                <th class="text-left px-4 py-3">Status</th>
                <th class="text-left px-4 py-3">Last Inform</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($devices as $d)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2 font-mono text-xs">{{ \Illuminate\Support\Str::limit($d->device_id, 32) }}</td>
                    <td class="px-4 py-2">{{ $d->serial_number ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $d->manufacturer ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $d->model_name ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $d->ssid ?? '—' }}</td>
                    <td class="px-4 py-2 font-mono">{{ $d->ip ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @if($d->status === 'online') <span class="text-emerald-600">● online</span>
                        @elseif($d->status === 'offline') <span class="text-slate-400">offline</span>
                        @else <span class="text-amber-500">{{ $d->status }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-xs">{{ optional($d->last_inform_at)->diffForHumans() ?? '—' }}</td>
                    <td class="px-4 py-2 text-right space-x-2 text-xs whitespace-nowrap">
                        <form method="POST" action="{{ route('genieacs.refresh', $d->device_id) }}" class="inline">@csrf<button class="text-blue-600 hover:underline">Refresh</button></form>
                        <form method="POST" action="{{ route('genieacs.reboot', $d->device_id) }}" class="inline" onsubmit="return confirm('Reboot ONU?')">@csrf<button class="text-rose-600 hover:underline">Reboot</button></form>
                        <button onclick="setSsid('{{ $d->device_id }}')" class="text-slate-700 hover:underline">SSID</button>
                        <button onclick="setPwd('{{ $d->device_id }}')" class="text-slate-700 hover:underline">WiFi Pwd</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-slate-400">Belum ada data GenieACS. Klik "Sync dari NBI".</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-slate-100">{{ $devices->links() }}</div>
</div>

<form id="frmSsid" method="POST" class="hidden"></form>
<form id="frmPwd"  method="POST" class="hidden"></form>

@push('scripts')
<script>
function setSsid(id) {
    const v = prompt('SSID baru:'); if (!v) return;
    const f = document.getElementById('frmSsid');
    f.action = `/genieacs/${encodeURIComponent(id)}/ssid`;
    f.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="ssid" value="${v.replace(/"/g,'&quot;')}">`;
    f.submit();
}
function setPwd(id) {
    const v = prompt('Password WiFi baru (min 8 karakter):'); if (!v) return;
    const f = document.getElementById('frmPwd');
    f.action = `/genieacs/${encodeURIComponent(id)}/password`;
    f.innerHTML = `<input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="password" value="${v.replace(/"/g,'&quot;')}">`;
    f.submit();
}
</script>
@endpush
@endsection
