@extends('layouts.app')
@section('title','Generate Voucher')

@section('content')
<div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Generate Voucher Hotspot</h1>
        <p class="text-sm text-ink/55">Bikin batch voucher → auto-insert ke FreeRADIUS → cetak ke A4 (5×6 = 30/lembar).</p>
    </div>
    <a href="{{ route('vouchers.index') }}" class="px-4 py-2 text-sm text-ink/60 hover:bg-cream-deep/60 rounded-xl">← Kembali ke list</a>
</div>

@if(session('success')) <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-3 mb-3 text-sm">{{ session('success') }}</div> @endif
@if($errors->any())     <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-3 mb-3 text-sm"><ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div> @endif

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    <form method="POST" action="{{ route('vouchers.generate') }}" class="col-span-2 bg-white border border-cream-deep/60 rounded-2xl p-5 space-y-4">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="col-span-2">
                <label class="text-sm font-medium">Profile / Paket</label>
                @if(count($groups) > 0)
                    <select name="profile" required class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                        <option value="">— Pilih paket voucher —</option>
                        @foreach($groups as $g)
                            <option value="{{ $g }}" @selected(old('profile') === $g)>{{ $g }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-ink/45 mt-1">Daftar dari grup RADIUS Hotspot* &amp; HS_*. Durasi voucher diatur di Session-Timeout grup tsb.</p>
                @else
                    <input name="profile" required value="{{ old('profile') }}" placeholder="Hotspot6Jam" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                    <p class="text-xs text-amber-700 mt-1">⚠ DB RADIUS belum konek atau belum ada grup Hotspot* / HS_*.</p>
                @endif
            </div>
            <div>
                <label class="text-sm font-medium">Jumlah Voucher</label>
                <input name="count" type="number" min="1" max="300" required value="{{ old('count', 30) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                <p class="text-xs text-ink/45 mt-1">Max 300 per batch. Default 30 = pas 1 lembar A4.</p>
            </div>
            <div>
                <label class="text-sm font-medium">Panjang Kode</label>
                <input name="code_length" type="number" min="4" max="12" required value="{{ old('code_length', 6) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                <p class="text-xs text-ink/45 mt-1">Default 6 karakter (mis. <code>9K3MX7</code>).</p>
            </div>
            <div class="min-w-0">
                <label class="text-sm font-medium">Prefix <span class="text-ink/40 font-normal">(opsional)</span></label>
                @if(!empty($tenantPrefix))
                    <div class="flex items-stretch mt-1 max-w-full">
                        <span class="px-2 sm:px-3 py-2 bg-cream-deep/70 border border-r-0 border-ink/10 rounded-l-lg font-mono text-xs sm:text-sm font-semibold whitespace-nowrap shrink-0 select-none">{{ $tenantPrefix }}</span>
                        <input name="prefix" maxlength="8" value="{{ old('prefix') }}" placeholder="AHN" class="flex-1 min-w-0 border border-ink/10 rounded-r-lg px-3 py-2 font-mono text-sm">
                    </div>
                    <p class="text-xs text-ink/45 mt-1">Voucher otomatis diawali <span class="font-mono font-semibold">{{ $tenantPrefix }}</span> (prefix tenant). Lo bisa kasih prefix tambahan atau kosongin. Mis. <code>{{ $tenantPrefix }}9K3MX7</code> atau <code>{{ $tenantPrefix }}AHN9K3</code>.</p>
                @else
                    <input name="prefix" maxlength="8" value="{{ old('prefix') }}" placeholder="AHN" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                    <p class="text-xs text-ink/45 mt-1">Mis. <code>AHN</code> → voucher jadi <code>AHN9K3MX7</code>.</p>
                @endif
            </div>
            <div>
                <label class="text-sm font-medium">Expiration <span class="text-ink/40 font-normal">(opsional)</span></label>
                <input name="expires_in_days" type="number" min="0" max="3650" value="{{ old('expires_in_days') }}" placeholder="30" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
                <p class="text-xs text-ink/45 mt-1">Kosongkan = tanpa expiration (voucher hidup sampai dipakai). Isi N hari = voucher tetap expire walau belum dipakai.</p>
            </div>
            <div class="col-span-2">
                <label class="text-sm font-medium">Label Batch <span class="text-ink/40 font-normal">(opsional)</span></label>
                <input name="label" maxlength="120" value="{{ old('label', 'Cetakan ' . now()->format('d M Y') . ' - Toko Setiabudi') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                <p class="text-xs text-ink/45 mt-1">Buat history. Bisa diisi nama outlet/lokasi/agen.</p>
            </div>
        </div>
        <div class="pt-3 border-t border-cream-deep/60 flex items-center gap-3">
            <button class="px-5 py-2.5 bg-ink text-white rounded-xl hover:bg-black font-semibold">Generate &amp; Cetak A4</button>
            <span class="text-xs text-ink/45">Setelah generate akan langsung diarahkan ke halaman cetak.</span>
        </div>
    </form>

    <aside class="bg-white border border-cream-deep/60 rounded-2xl p-5">
        <h3 class="font-semibold mb-3">Riwayat Batch (20 terakhir)</h3>
        @forelse($batches as $b)
            <div class="border-t border-cream-deep/40 py-3 first:border-t-0">
                <div class="text-sm font-semibold">{{ $b->label ?? 'Batch #'.$b->id }}</div>
                <div class="text-xs text-ink/55 mt-0.5">
                    {{ $b->profile }} · {{ $b->count }} voucher · {{ $b->created_at->diffForHumans() }}
                </div>
                <div class="flex gap-3 mt-1.5 text-xs">
                    <a href="{{ route('vouchers.batch.print', $b->id) }}" target="_blank" class="text-ink hover:underline">🖨 Cetak ulang</a>
                    <a href="{{ route('vouchers.batch.show', $b->id) }}" class="text-ink/55 hover:underline">Detail</a>
                    <form method="POST" action="{{ route('vouchers.batch.destroy', $b->id) }}" class="inline" onsubmit="return confirm('Hapus batch + {{ $b->count }} voucher di RADIUS?');">
                        @csrf @method('DELETE')
                        <button class="text-rose-600 hover:underline">Hapus</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="text-sm text-ink/45 py-6 text-center">Belum ada batch voucher.</div>
        @endforelse
    </aside>
</div>
@endsection
