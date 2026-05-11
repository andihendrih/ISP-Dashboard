@extends('layouts.app')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('settings.tenants.index') }}" class="text-sm text-ink/60 hover:text-ink">&larr; Kembali ke list</a>
</div>

<div class="mb-6">
    <h1 class="text-xl sm:text-2xl font-bold text-rose-900">Hapus Tenant</h1>
    <p class="text-sm text-ink/55">Pilih tenant yang mau dihapus. Action ini gak bisa di-undo. Tenant default <strong>ahnet</strong> tidak muncul di list (gak boleh dihapus).</p>
</div>

@if(session('error'))
    <div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>
@endif

<form method="GET" action="{{ route('settings.tenants.delete-form') }}" class="bg-white rounded-3xl shadow-card p-5 mb-5">
    <label class="text-xs uppercase tracking-widest text-ink/45 font-semibold">1. Pilih Tenant</label>
    <div class="flex flex-col sm:flex-row gap-2 mt-2">
        <select name="tenant_id" class="flex-1 border border-ink/10 rounded-lg px-3 py-2 text-sm">
            <option value="">— pilih tenant —</option>
            @foreach($tenants as $t)
                <option value="{{ $t->id }}" {{ $selected && $selected->id === $t->id ? 'selected' : '' }}>
                    {{ $t->name }} ({{ $t->code }}) — plan {{ $t->plan }}{{ $t->is_active ? '' : ' [nonaktif]' }}
                </option>
            @endforeach
        </select>
        <button class="px-4 py-2 bg-ink/10 hover:bg-ink/20 rounded-xl text-sm">Tampilkan Detail</button>
    </div>
    @if($tenants->isEmpty())
        <p class="text-xs text-ink/50 mt-2">Belum ada tenant lain selain default. Bikin tenant dulu lewat menu Tambah Tenant.</p>
    @endif
</form>

@if($selected)
    @php
        $totalRows = collect($stats ?? [])->sum();
    @endphp
    <div class="bg-white rounded-3xl shadow-card p-5 mb-5">
        <div class="text-xs uppercase tracking-widest text-ink/45 font-semibold mb-2">2. Data yang Akan Terdampak</div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mt-3 text-sm">
            <div class="flex justify-between border-b border-ink/5 py-1.5">
                <span>Nama Tenant</span><span class="font-semibold">{{ $selected->name }}</span>
            </div>
            <div class="flex justify-between border-b border-ink/5 py-1.5">
                <span>Code</span><span class="font-mono">{{ $selected->code }}</span>
            </div>
            @foreach($stats as $tbl => $cnt)
                <div class="flex justify-between border-b border-ink/5 py-1.5 {{ $cnt > 0 ? 'text-rose-700' : 'text-ink/40' }}">
                    <span class="font-mono text-xs">{{ $tbl }}</span>
                    <span class="font-semibold">{{ number_format($cnt) }}</span>
                </div>
            @endforeach
        </div>
        @if($totalRows > 0)
            <div class="mt-4 px-4 py-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-900 text-sm">
                <strong>Tenant ini punya {{ number_format($totalRows) }} baris data.</strong>
                Lo harus pakai <strong>mode Cascade</strong> kalau memang mau hapus semuanya.
                Mode Safe akan ditolak.
            </div>
        @else
            <div class="mt-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-sm">
                <strong>Tenant ini kosong.</strong> Mode Safe maupun Cascade dua-duanya OK.
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('settings.tenants.delete-execute') }}" class="bg-white rounded-3xl shadow-card p-5 space-y-4 border-2 border-rose-200"
          onsubmit="return confirm('Yakin hapus tenant {{ $selected->name }} ({{ $selected->code }})? Action ini gak bisa di-undo.');">
        @csrf
        <input type="hidden" name="tenant_id" value="{{ $selected->id }}">

        <div class="text-xs uppercase tracking-widest text-rose-900 font-semibold">3. Mode Hapus</div>

        <label class="flex items-start gap-3 p-3 rounded-xl border border-ink/10 hover:bg-cream-deep/30 cursor-pointer">
            <input type="radio" name="mode" value="safe" {{ $totalRows === 0 ? 'checked' : '' }} class="mt-1">
            <div>
                <div class="font-semibold text-sm">Safe — cuma kalau kosong</div>
                <div class="text-xs text-ink/55">Hapus tenant tapi tolak kalau masih ada data (customer/invoice/user). Aman, gak bisa salah.</div>
            </div>
        </label>

        <label class="flex items-start gap-3 p-3 rounded-xl border border-rose-300 bg-rose-50/50 hover:bg-rose-50 cursor-pointer">
            <input type="radio" name="mode" value="cascade" {{ $totalRows > 0 ? 'checked' : '' }} class="mt-1">
            <div>
                <div class="font-semibold text-sm text-rose-900">Cascade — hapus tenant + SEMUA data</div>
                <div class="text-xs text-rose-800/80">
                    Hapus tenant beserta {{ number_format($totalRows) }} baris data terkait
                    (customers, invoices, payments, users, devices, vouchers, tickets, RADIUS users, dll).
                    <strong>Gak bisa di-undo.</strong>
                </div>
            </div>
        </label>

        <div>
            <label class="text-xs uppercase tracking-widest text-rose-900 font-semibold">4. Konfirmasi</label>
            <p class="text-xs text-ink/60 mt-1">Ketik code tenant <code class="px-1.5 py-0.5 rounded bg-cream-deep font-mono">{{ $selected->code }}</code> di bawah untuk unlock tombol delete:</p>
            <input type="text" name="confirm_code" required autocomplete="off"
                   placeholder="ketik: {{ $selected->code }}"
                   class="w-full mt-2 border border-rose-200 rounded-lg px-3 py-2 text-sm font-mono"
                   oninput="document.getElementById('btn-delete').disabled = (this.value.trim().toLowerCase() !== '{{ strtolower($selected->code) }}');">
        </div>

        <div class="flex items-center gap-3 pt-2">
            <button id="btn-delete" disabled type="submit"
                    class="px-5 py-2.5 bg-rose-600 text-white rounded-xl shadow-card hover:bg-rose-700 disabled:bg-rose-300 disabled:cursor-not-allowed text-sm font-semibold">
                Hapus Tenant Sekarang
            </button>
            <a href="{{ route('settings.tenants.index') }}" class="text-sm text-ink/60 hover:text-ink">Batal</a>
        </div>
    </form>
@endif
@endsection
