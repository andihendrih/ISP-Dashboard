@extends('layouts.app')
@section('title','Edit Plan')
@section('breadcrumb','Tenant Billing / Plans / Edit')

@section('content')
<div class="max-w-3xl">
    <div class="mb-4">
        <h1 class="text-xl sm:text-2xl font-bold">Edit Plan — {{ $plan->name }}</h1>
        <p class="text-sm text-ink/55 font-mono">{{ $plan->code }}</p>
    </div>
    <form method="POST" action="{{ route('tenant_billing.plans.update', $plan) }}" class="bg-white rounded-3xl shadow-card p-4 sm:p-6">
        @method('PUT')
        @include('admin.tenant-billing.plans._form')
        <div class="mt-6 flex items-center gap-2">
            <button class="px-5 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Simpan</button>
            <a href="{{ route('tenant_billing.plans.index') }}" class="px-4 py-2 text-ink/60 hover:bg-cream-deep/60 rounded-xl text-sm">Batal</a>
        </div>
    </form>
</div>
@endsection
