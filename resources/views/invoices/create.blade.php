@extends('layouts.app')
@section('title','Buat Invoice Manual')
@section('breadcrumb','Billing / Tagihan / Buat Manual')

@section('content')
<h1 class="text-xl sm:text-2xl font-bold mb-4">Buat Invoice Manual</h1>

<form method="POST" action="{{ route('invoices.store') }}" class="bg-white rounded-3xl shadow-card p-6 max-w-2xl space-y-4">
    @csrf
    <div>
        <label class="text-sm font-medium">Pelanggan</label>
        <select name="customer_profile_id" required class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            <option value="">— Pilih pelanggan —</option>
            @foreach($customers as $c)
                <option value="{{ $c->id }}">{{ $c->full_name }} ({{ $c->customer_code }})</option>
            @endforeach
        </select>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <label class="text-sm font-medium">Periode (YYYY-MM)</label>
            <input name="period" type="text" value="{{ now()->format('Y-m') }}" pattern="\d{4}-\d{2}" required class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
        <div>
            <label class="text-sm font-medium">Tgl Jatuh Tempo (1-31)</label>
            <input name="due_day" type="number" min="1" max="31" value="{{ config('ahnet.billing.due_day') }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
        </div>
    </div>
    <p class="text-xs text-ink/55">
        Harga & PPN akan otomatis diambil dari paket layanan pelanggan.
        Jika tanggal pasang ≥ 2 di bulan tersebut → invoice diprorate.
    </p>
    <div class="flex gap-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl hover:bg-black">Buat Invoice</button>
        <a href="{{ route('invoices.index') }}" class="px-5 py-2 rounded-xl text-ink/60">Batal</a>
    </div>
</form>
@endsection
