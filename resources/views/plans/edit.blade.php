@extends('layouts.app')
@section('title','Edit Paket')
@section('breadcrumb','Billing / Paket / Edit')

@section('content')
<h1 class="text-xl sm:text-2xl font-bold mb-4">Edit Paket: {{ $plan->name }}</h1>

<form method="POST" action="{{ route('plans.update', $plan) }}" class="bg-white rounded-3xl shadow-card p-6 max-w-2xl space-y-4">
    @csrf @method('PUT')
    @include('plans._form', ['plan' => $plan])
    <div class="flex gap-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl hover:bg-black">Update</button>
        <a href="{{ route('plans.index') }}" class="px-5 py-2 rounded-xl text-ink/60 hover:bg-cream-deep/60">Batal</a>
    </div>
</form>
@endsection
