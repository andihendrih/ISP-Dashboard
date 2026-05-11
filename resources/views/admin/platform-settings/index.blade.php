@extends('layouts.app')
@section('title','Platform Settings')
@section('breadcrumb','Pengaturan / Platform Settings')

@php
    $tabs = [
        'brand'    => 'Brand Platform',
        'bank'     => 'Bank Info',
        'whatsapp' => 'WhatsApp',
        'email'    => 'Email',
    ];
@endphp

@section('content')
<div class="mb-4">
    <h1 class="text-xl sm:text-2xl font-bold">Platform Settings</h1>
    <p class="text-sm text-ink/55">Config superadmin — branding, rekening, WA &amp; email yang lo (platform owner) pakai untuk <strong>menagih tenant</strong> yang sewa portal ini.</p>
</div>

@if(session('success'))<div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ session('error') }}</div>@endif
@if($errors->any())<div class="bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl p-3 mb-4 text-sm">{{ $errors->first() }}</div>@endif

<div class="grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-4">
    <div class="lg:sticky lg:top-4 self-start">
        <div class="bg-white rounded-3xl shadow-card p-2 flex lg:flex-col gap-1 overflow-x-auto">
            @foreach($tabs as $key => $label)
                <a href="{{ route('admin.platform_settings.'.$key) }}" class="px-3 py-2 rounded-xl text-sm whitespace-nowrap {{ $tab === $key ? 'bg-ink text-white shadow-card' : 'text-ink/70 hover:bg-cream-deep/60' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-card p-4 sm:p-6">
        @include('admin.platform-settings.partials.'.$tab, ['setting' => $setting])
    </div>
</div>
@endsection
