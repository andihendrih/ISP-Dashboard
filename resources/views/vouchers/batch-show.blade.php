@extends('layouts.app')
@section('title','Detail Batch Voucher')

@section('content')
<div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
    <div>
        <h1 class="text-2xl font-bold">{{ $batch->label ?? 'Batch #'.$batch->id }}</h1>
        <p class="text-sm text-ink/55">
            Profile <code>{{ $batch->profile }}</code> ·
            {{ $batch->count }} voucher ·
            dibuat {{ $batch->created_at->format('d M Y H:i') }}
            @if($batch->expires_at) · expire {{ $batch->expires_at->format('d M Y') }} @endif
        </p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('vouchers.batch.print', $batch->id) }}" target="_blank" class="px-4 py-2 bg-accent text-ink rounded-xl text-sm font-semibold">🖨 Cetak A4</a>
        <a href="{{ route('vouchers.generate-form') }}" class="px-4 py-2 text-sm text-ink/60 hover:bg-cream-deep/60 rounded-xl">← Kembali</a>
    </div>
</div>

<div class="bg-white border border-cream-deep/60 rounded-2xl p-5">
    <div class="grid grid-cols-6 gap-2 font-mono text-sm">
        @foreach($batch->codes as $code)
            <div class="border border-ink/10 rounded-lg px-2 py-1.5 text-center">{{ $code }}</div>
        @endforeach
    </div>
</div>
@endsection
