@extends('layouts.app')
@section('title','SNMP Monitor')

@section('content')
<h1 class="text-xl font-bold mb-4">SNMP Monitor</h1>

<form method="GET" class="mb-4 flex gap-2">
    <select name="device_id" class="border-slate-300 rounded-lg text-sm">
        @foreach($devices as $d)
            <option value="{{ $d->id }}" @selected($device && $device->id === $d->id)>{{ $d->name }} ({{ $d->host }})</option>
        @endforeach
    </select>
    <button class="px-3 py-1.5 bg-slate-700 text-white rounded-lg text-sm">Pilih</button>
    @if($device)
        <form method="POST" action="{{ route('snmp.poll', $device->id) }}" class="inline">
            @csrf
            <button class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-sm">Poll Sekarang</button>
        </form>
    @endif
</form>

@if($device)
    <div class="bg-white rounded-xl border border-slate-100 mb-4">
        <div class="px-5 py-3 font-semibold border-b border-slate-100">Interface Stats — {{ $device->name }}</div>
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-slate-600"><tr>
                <th class="text-left px-4 py-2">ifIndex</th>
                <th class="text-left px-4 py-2">Name</th>
                <th class="text-right px-4 py-2">In bps</th>
                <th class="text-right px-4 py-2">Out bps</th>
                <th class="text-left px-4 py-2">Status</th>
                <th class="text-left px-4 py-2">Polled</th>
                <th></th>
            </tr></thead>
            <tbody>
                @forelse($latest as $l)
                    <tr class="border-t border-slate-100">
                        <td class="px-4 py-2 font-mono">{{ $l->if_index }}</td>
                        <td class="px-4 py-2 font-mono">{{ $l->if_name }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($l->in_bps) }}</td>
                        <td class="px-4 py-2 text-right">{{ number_format($l->out_bps) }}</td>
                        <td class="px-4 py-2">{{ $l->oper_status ? '🟢 up' : '🔴 down' }}</td>
                        <td class="px-4 py-2 text-xs text-slate-500">{{ $l->polled_at->diffForHumans() }}</td>
                        <td class="px-4 py-2"><button onclick="loadHistory({{ $l->if_index }}, '{{ $l->if_name }}')" class="text-blue-600 text-xs hover:underline">History</button></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400">Belum ada data SNMP. Klik "Poll Sekarang".</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div id="chartCard" class="bg-white rounded-xl border border-slate-100 p-5 hidden">
        <div class="font-semibold mb-3" id="chartTitle">History</div>
        <canvas id="chartHist" height="80"></canvas>
    </div>
@endif

@push('scripts')
<script>
let chartHist;
async function loadHistory(ifIndex, name) {
    const url = "{{ $device ? route('snmp.history', $device->id) : '' }}" + `?if_index=${ifIndex}&minutes=120`;
    const res = await fetch(url);
    const data = await res.json();
    document.getElementById('chartCard').classList.remove('hidden');
    document.getElementById('chartTitle').textContent = `History — ${name}`;
    const ctx = document.getElementById('chartHist').getContext('2d');
    if (chartHist) chartHist.destroy();
    chartHist = new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.labels,
            datasets: [
                { label: 'In bps',  data: data.in,  borderColor: '#3b82f6', tension: 0.3 },
                { label: 'Out bps', data: data.out, borderColor: '#10b981', tension: 0.3 },
            ]
        },
        options: { plugins: { legend: { position: 'bottom' } } }
    });
}
</script>
@endpush
@endsection
