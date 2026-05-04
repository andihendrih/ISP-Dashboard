@extends('layouts.app')
@section('title', 'Perangkat ' . $device->serial_number)
@section('breadcrumb','Inventory / Detail')

@section('content')
@php($badge = $device->statusBadge())

<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold font-mono">{{ $device->serial_number }}</h1>
        <p class="text-sm text-ink/55">{{ $device->typeLabel() }} · {{ $device->brand ?: '—' }} {{ $device->model }}</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('devices.edit', $device) }}" class="px-3 py-2 border border-ink/10 rounded-xl text-sm bg-white">Edit</a>
        <a href="{{ route('devices.index') }}" class="px-3 py-2 text-sm text-ink/55 hover:underline self-center">&larr; Kembali</a>
    </div>
</div>

@if(session('success'))
    <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    {{-- Main info --}}
    <div class="lg:col-span-2 bg-white rounded-2xl border border-cream-deep/60 shadow-sm p-5">
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xs rounded-full px-2 py-0.5 border {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            <span class="text-xs bg-cream-deep/60 border border-ink/10 rounded-full px-2 py-0.5">{{ $device->typeLabel() }}</span>
        </div>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-xs text-ink/55">Serial Number</dt>
                <dd class="font-mono">{{ $device->serial_number }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink/55">MAC Address</dt>
                <dd class="font-mono">{{ $device->mac_address ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink/55">Brand</dt>
                <dd>{{ $device->brand ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink/55">Model</dt>
                <dd>{{ $device->model ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink/55">Harga Beli</dt>
                <dd>{{ $device->purchase_price ? 'Rp ' . number_format($device->purchase_price, 0, ',', '.') : '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink/55">Tanggal Beli</dt>
                <dd>{{ $device->purchased_at?->format('d M Y') ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink/55">Lokasi Gudang</dt>
                <dd>{{ $device->warehouse_location ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs text-ink/55">Pelanggan Terpasang</dt>
                <dd>
                    @if($device->customer)
                        <a href="{{ route('customers.edit', $device->customer_id) }}" class="text-blue-600 hover:underline">
                            {{ $device->customer->full_name }} ({{ $device->customer->customer_code }})
                        </a>
                    @else
                        <span class="text-ink/40">—</span>
                    @endif
                </dd>
            </div>
            @if($device->notes)
                <div class="sm:col-span-2">
                    <dt class="text-xs text-ink/55">Catatan</dt>
                    <dd class="whitespace-pre-line">{{ $device->notes }}</dd>
                </div>
            @endif
        </dl>

        {{-- History --}}
        <div class="mt-6">
            <h2 class="text-sm font-bold mb-2">Riwayat Mutasi</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-cream-deep/60 text-ink/70 text-xs uppercase">
                        <tr>
                            <th class="text-left px-3 py-2">Waktu</th>
                            <th class="text-left px-3 py-2">Aksi</th>
                            <th class="text-left px-3 py-2">Pelanggan</th>
                            <th class="text-left px-3 py-2">Teknisi</th>
                            <th class="text-left px-3 py-2">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($device->assignments as $a)
                            <tr class="border-t border-cream-deep/60">
                                <td class="px-3 py-2 whitespace-nowrap">{{ $a->acted_at?->format('d M Y H:i') }}</td>
                                <td class="px-3 py-2">{{ $a->actionLabel() }}</td>
                                <td class="px-3 py-2">{{ $a->customer?->full_name ?? '—' }}</td>
                                <td class="px-3 py-2">{{ $a->technician?->name ?? '—' }}</td>
                                <td class="px-3 py-2 text-ink/55">{{ $a->notes ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-6 text-center text-ink/40">Belum ada riwayat.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div class="space-y-4">
        @if(!in_array($device->status, ['assigned', 'retired']))
            <div class="bg-white rounded-2xl border border-cream-deep/60 shadow-sm p-4">
                <h3 class="text-sm font-bold mb-2">Pasang ke Pelanggan</h3>
                <form method="POST" action="{{ route('devices.assign', $device) }}">
                    @csrf
                    <select name="customer_id" required class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white mb-2">
                        <option value="">— Pilih Pelanggan —</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->customer_code }} · {{ $c->full_name }}</option>
                        @endforeach
                    </select>
                    <textarea name="notes" rows="2" placeholder="Catatan pemasangan (opsional)" class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white mb-2"></textarea>
                    <button class="w-full px-4 py-2 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 text-sm">Pasang &amp; Install</button>
                </form>
            </div>
        @endif

        <div class="bg-white rounded-2xl border border-cream-deep/60 shadow-sm p-4">
            <h3 class="text-sm font-bold mb-2">Ubah Status</h3>
            <form method="POST" action="{{ route('devices.transition', $device) }}" class="space-y-2">
                @csrf
                <select name="action" required class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white">
                    @if($device->status === 'assigned')
                        <option value="return">Tarik dari Pelanggan (→ Stock)</option>
                        <option value="repair">Tarik &amp; Tandai Rusak</option>
                        <option value="mark_lost">Tandai Hilang</option>
                    @else
                        <option value="mark_stock">Kembalikan ke Stock</option>
                        <option value="repair">Tandai Rusak</option>
                        <option value="mark_lost">Tandai Hilang</option>
                        <option value="retire">Pensiun</option>
                    @endif
                </select>
                <textarea name="notes" rows="2" placeholder="Catatan (opsional)" class="w-full border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white"></textarea>
                <button class="w-full px-4 py-2 bg-ink text-white rounded-xl hover:bg-ink text-sm">Update Status</button>
            </form>
        </div>

        <form method="POST" action="{{ route('devices.destroy', $device) }}" onsubmit="return confirm('Hapus perangkat ini? Riwayat juga akan terhapus.');">
            @csrf
            @method('DELETE')
            <button class="w-full px-4 py-2 border border-rose-300 text-rose-700 rounded-xl hover:bg-rose-50 text-sm">Hapus Perangkat</button>
        </form>
    </div>
</div>
@endsection
