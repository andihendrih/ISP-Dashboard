@extends('layouts.app')
@section('title','Tambah Paket')
@section('breadcrumb','Billing / Paket / Baru')

@section('content')
<h1 class="text-2xl font-bold mb-4">Tambah Paket Layanan</h1>

<form method="POST" action="{{ route('plans.store') }}" class="bg-white rounded-3xl shadow-card p-6 max-w-2xl space-y-4">
    @csrf
    @include('plans._form', ['plan' => null])
    <div class="flex gap-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl hover:bg-black">Simpan</button>
        <a href="{{ route('plans.index') }}" class="px-5 py-2 rounded-xl text-ink/60 hover:bg-cream-deep/60">Batal</a>
    </div>
</form>
@endsection
