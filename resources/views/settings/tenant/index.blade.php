@extends('layouts.app')
@section('title','Settings Tenant')
@section('breadcrumb','Pengaturan / Settings Tenant')

@php
    $tabs = [
        'brand' => ['Brand', 'M5 13l4 4L19 7'],
        'whatsapp' => ['WhatsApp', 'M12 21a9 9 0 100-18 9 9 0 000 18z'],
        'email' => ['Email', 'M4 4h16v16H4z'],
        'gateway' => ['Payment Gateway', 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2'],
    ];
@endphp

@section('content')
<div class="mb-4">
    <h1 class="text-xl sm:text-2xl font-bold">Settings Tenant</h1>
    <p class="text-sm text-ink/55">{{ $tenant->name }} <span class="font-mono text-xs text-ink/45">({{ $tenant->code }})</span> — config brand, WA, email, &amp; payment gateway untuk menagih pelanggan tenant ini.</p>
</div>

@if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif
@if($errors->any())<div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ $errors->first() }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-4">
    <div class="lg:sticky lg:top-4 self-start">
        <div class="bg-white rounded-3xl shadow-card p-2 flex lg:flex-col gap-1 overflow-x-auto">
            @foreach($tabs as $key => [$label, $icon])
                <a href="{{ route('settings.tenant_settings.'.$key) }}" class="px-3 py-2 rounded-xl text-sm whitespace-nowrap {{ $tab === $key ? 'bg-ink text-white shadow-card' : 'text-ink/70 hover:bg-cream-deep/60' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-card p-4 sm:p-6">
        @include('settings.tenant.partials.'.$tab, ['setting' => $setting])
    </div>
</div>
@endsection
