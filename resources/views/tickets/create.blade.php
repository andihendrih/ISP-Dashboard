@extends('layouts.app')
@section('title','Tiket Baru')

@section('content')
<div class="mb-4">
    <h1 class="text-2xl font-bold">Tiket Baru</h1>
    <p class="text-sm text-ink/55">Catat komplain atau permintaan pelanggan AHNet.</p>
</div>

@if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-2xl p-3 mb-4 text-sm">
        <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('tickets.store') }}" class="bg-white p-6 rounded-2xl border border-cream-deep/60 max-w-3xl space-y-4">
    @csrf
    <div class="grid grid-cols-2 gap-3">
        <div class="col-span-2">
            <label class="text-sm font-medium">Pelanggan <span class="text-ink/40">(opsional)</span></label>
            <select name="customer_profile_id" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                <option value="">— Tidak terkait pelanggan —</option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" data-phone="{{ $c->phone }}" @selected((int) old('customer_profile_id') === $c->id)>
                        {{ $c->customer_code }} — {{ $c->full_name }}@if($c->phone) ({{ $c->phone }})@endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-span-2">
            <label class="text-sm font-medium">Subject <span class="text-red-500">*</span></label>
            <input name="subject" required maxlength="200" value="{{ old('subject') }}" placeholder="contoh: Internet putus sejak pagi" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Kategori</label>
            <select name="category" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                @foreach(['gangguan' => 'Gangguan', 'billing' => 'Billing', 'instalasi' => 'Instalasi', 'pindah_alamat' => 'Pindah Alamat', 'upgrade' => 'Upgrade Paket', 'lainnya' => 'Lainnya'] as $k => $v)
                    <option value="{{ $k }}" @selected(old('category', 'gangguan') === $k)>{{ $v }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium">Prioritas</label>
            <select name="priority" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                @foreach(['low','normal','high','urgent'] as $p)
                    <option value="{{ $p }}" @selected(old('priority', 'normal') === $p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-sm font-medium">No. Telp Kontak</label>
            <input name="contact_phone" id="cphone" value="{{ old('contact_phone') }}" placeholder="auto dari pelanggan" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Assign ke <span class="text-ink/40">(opsional)</span></label>
            <select name="assigned_to" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
                <option value="">— Belum di-assign —</option>
                @foreach(\App\Models\User::orderBy('name')->get() as $u)
                    <option value="{{ $u->id }}" @selected((int) old('assigned_to') === $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-span-2">
            <label class="text-sm font-medium">Deskripsi <span class="text-red-500">*</span></label>
            <textarea name="description" rows="6" required maxlength="5000" placeholder="Jelasin detail keluhan / permintaan…" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">{{ old('description') }}</textarea>
        </div>
    </div>

    <div class="flex gap-2 pt-2">
        <button class="px-5 py-2.5 bg-accent text-ink rounded-xl font-semibold hover:brightness-95">Buat Tiket</button>
        <a href="{{ route('tickets.index') }}" class="px-5 py-2.5 rounded-xl text-ink/60 hover:bg-cream-deep/60">Batal</a>
    </div>
</form>

<script>
// Auto-fill phone dari customer kalau dipilih
document.querySelector('select[name="customer_profile_id"]').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const phone = opt.getAttribute('data-phone');
    const inp = document.getElementById('cphone');
    if (phone && !inp.value) inp.value = phone;
});
</script>
@endsection
