@extends('layouts.app')
@section('title', $device->name)

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold">{{ $device->name }}</h1>
        <div class="text-sm text-slate-500 font-mono">{{ $device->host }}:{{ $device->api_port }}</div>
    </div>
    <form method="POST" action="{{ route('mikrotik.destroy', $device->id) }}" onsubmit="return confirm('Hapus device?')">
        @csrf @method('DELETE')
        <button class="text-rose-600 text-sm hover:underline">Hapus device</button>
    </form>
</div>

@if($error)
    <div class="mb-4 p-4 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-sm">
        Tidak bisa konek ke router: {{ $error }}
    </div>
@endif

@if($info)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        <div class="bg-white p-5 rounded-xl border border-slate-100">
            <div class="font-semibold mb-2">/system/identity</div>
            <pre class="text-xs">{{ json_encode($info['identity'], JSON_PRETTY_PRINT) }}</pre>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-100">
            <div class="font-semibold mb-2">/system/resource</div>
            <pre class="text-xs">{{ json_encode($info['resource'], JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
@endif

<div class="bg-white rounded-xl border border-slate-100 mb-4">
    <div class="px-5 py-3 font-semibold border-b border-slate-100">PPP Active Sessions</div>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
            <tr><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Address</th><th class="text-left px-4 py-2">Uptime</th><th class="text-left px-4 py-2">Caller</th><th></th></tr>
        </thead>
        <tbody>
            @forelse($active as $a)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2 font-mono">{{ $a['name'] ?? '' }}</td>
                    <td class="px-4 py-2 font-mono">{{ $a['address'] ?? '' }}</td>
                    <td class="px-4 py-2">{{ $a['uptime'] ?? '' }}</td>
                    <td class="px-4 py-2 font-mono">{{ $a['caller-id'] ?? '' }}</td>
                    <td class="px-4 py-2 text-right">
                        <form method="POST" action="{{ route('mikrotik.disconnect', $device->id) }}">
                            @csrf
                            <input type="hidden" name="username" value="{{ $a['name'] ?? '' }}">
                            <button class="text-rose-600 text-xs hover:underline">Disconnect</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-slate-400 text-center">Tidak ada sesi aktif.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="bg-white rounded-xl border border-slate-100">
    <div class="px-5 py-3 font-semibold border-b border-slate-100">PPP Profiles</div>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-600"><tr><th class="text-left px-4 py-2">Name</th><th class="text-left px-4 py-2">Rate Limit</th><th class="text-left px-4 py-2">Local-Address</th><th class="text-left px-4 py-2">Remote-Address</th></tr></thead>
        <tbody>
            @forelse($profiles as $p)
                <tr class="border-t border-slate-100">
                    <td class="px-4 py-2 font-mono">{{ $p['name'] ?? '' }}</td>
                    <td class="px-4 py-2 font-mono">{{ $p['rate-limit'] ?? '' }}</td>
                    <td class="px-4 py-2">{{ $p['local-address'] ?? '' }}</td>
                    <td class="px-4 py-2">{{ $p['remote-address'] ?? '' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-6 text-slate-400 text-center">Tidak ada profile.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
