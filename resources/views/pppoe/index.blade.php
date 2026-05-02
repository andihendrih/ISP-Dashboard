@extends('layouts.app')
@section('title','PPPoE Users')

@section('content')
<div class="flex items-center justify-between mb-4">
    <div>
        <h1 class="text-xl font-bold">PPPoE Users</h1>
        <p class="text-sm text-ink/55">Kelola akun PPPoE di FreeRADIUS.</p>
    </div>
    <a href="{{ route('pppoe.create') }}" class="px-4 py-2 bg-ink text-white rounded-lg text-sm">+ Buat User</a>
</div>

@if(session('bulk_created'))
    <div class="mb-4 bg-white shadow-card rounded-xl p-4">
        <div class="font-semibold mb-2 text-sm">Hasil Bulk Create:</div>
        <div class="overflow-x-auto"><table class="text-xs"><thead><tr><th class="pr-4 text-left">Username</th><th class="text-left">Password</th></tr></thead>
        <tbody>@foreach(session('bulk_created') as $row)<tr><td class="pr-4 font-mono">{{ $row['username'] }}</td><td class="font-mono">{{ $row['password'] }}</td></tr>@endforeach</tbody></table></div>
    </div>
@endif

<div class="bg-white rounded-xl border border-cream-deep/60 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-cream-card text-ink/65">
                <tr>
                    <th class="text-left px-4 py-3">Username</th>
                    <th class="text-left px-4 py-3">Password</th>
                    <th class="text-left px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-t border-cream-deep/60">
                        <td class="px-4 py-2 font-mono">{{ $row->username }}</td>
                        <td class="px-4 py-2 font-mono">{{ $row->value }}</td>
                        <td class="px-4 py-2 flex gap-2">
                            <form method="POST" action="{{ route('pppoe.reset-password', $row->username) }}">@csrf
                                <button class="text-amber-600 hover:underline text-xs">Reset Password</button>
                            </form>
                            <form method="POST" action="{{ route('pppoe.destroy', $row->username) }}" onsubmit="return confirm('Hapus user ini?')">
                                @csrf @method('DELETE')
                                <button class="text-rose-600 hover:underline text-xs">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-ink/45">Belum ada data — pastikan koneksi RADIUS terkonfigurasi di .env.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if(method_exists($rows, 'links'))
        <div class="px-4 py-3 border-t border-cream-deep/60">{{ $rows->links() }}</div>
    @endif
</div>
@endsection
