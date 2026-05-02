@extends('layouts.app')
@section('title','Pengaturan Notifikasi')

@section('content')
<div class="flex items-start justify-between gap-3 mb-4">
    <div>
        <h1 class="text-2xl font-bold">Pengaturan Notifikasi</h1>
        <p class="text-sm text-ink/55">WhatsApp &amp; Email reminder pembayaran. Provider WA aktif: <span class="font-mono px-2 py-0.5 rounded bg-cream-deep/60 text-ink">{{ $provider }}</span></p>
    </div>
    <a href="{{ route('notifications.logs') }}" class="px-4 py-2 bg-ink text-cream rounded-xl text-sm font-semibold hover:brightness-95">📋 Log Notifikasi</a>
</div>

@if(session('success')) <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-3 mb-3 text-sm">{{ session('success') }}</div> @endif
@if(session('error'))   <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-3 mb-3 text-sm">{{ session('error') }}</div> @endif

<div class="grid grid-cols-3 gap-4">
    <div class="col-span-2 space-y-4">
        <form method="POST" action="{{ route('notifications.settings.save') }}" class="bg-white border border-cream-deep/60 rounded-2xl p-5">
            @csrf
            <h2 class="text-base font-bold mb-3">Channel</h2>
            <label class="flex items-center justify-between py-2 border-b border-cream-deep/40">
                <div>
                    <div class="font-semibold">WhatsApp</div>
                    <div class="text-xs text-ink/55">Provider: {{ $provider }} (set <code>WA_PROVIDER</code> di .env)</div>
                </div>
                <input type="checkbox" name="channel_wa" class="w-5 h-5" @checked($settings['channel.wa'])>
            </label>
            <label class="flex items-center justify-between py-2">
                <div>
                    <div class="font-semibold">Email</div>
                    <div class="text-xs text-ink/55">Pakai Laravel Mail (set <code>MAIL_*</code> di .env)</div>
                </div>
                <input type="checkbox" name="channel_email" class="w-5 h-5" @checked($settings['channel.email'])>
            </label>

            <h2 class="text-base font-bold mt-5 mb-3">Template</h2>
            @php
                $templates = [
                    'template.invoice_created'  => ['Invoice baru terbit', 'Dikirim saat invoice baru di-generate.'],
                    'template.reminder_h3'      => ['Reminder H-3 jatuh tempo', 'Dikirim 3 hari sebelum due_date oleh scheduler 08:00.'],
                    'template.reminder_h0'      => ['Reminder hari-H jatuh tempo', 'Dikirim tepat hari due_date.'],
                    'template.reminder_overdue' => ['Reminder overdue (H+3)', 'Dikirim 3 hari setelah due_date untuk yg belum bayar.'],
                    'template.payment_received' => ['Pembayaran diterima', 'Dikirim setelah admin tandai invoice lunas.'],
                ];
            @endphp
            @foreach($templates as $k => [$lbl, $desc])
                <label class="flex items-center justify-between py-2 border-b border-cream-deep/40 last:border-0">
                    <div>
                        <div class="font-medium text-sm">{{ $lbl }}</div>
                        <div class="text-xs text-ink/55">{{ $desc }}</div>
                    </div>
                    <input type="checkbox" name="{{ str_replace('.', '_', $k) }}" class="w-5 h-5" @checked($settings[$k])>
                </label>
            @endforeach

            <button class="mt-4 px-5 py-2 bg-accent text-ink rounded-xl text-sm font-semibold">Simpan Pengaturan</button>
        </form>
    </div>

    <div class="space-y-4">
        <form method="POST" action="{{ route('notifications.test') }}" class="bg-white border border-cream-deep/60 rounded-2xl p-5">
            @csrf
            <h2 class="text-base font-bold mb-3">🚀 Kirim Test</h2>
            <label class="block text-xs text-ink/55 mb-1">Channel</label>
            <select name="channel" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm mb-3">
                <option value="wa">WhatsApp</option>
                <option value="email">Email</option>
            </select>
            <label class="block text-xs text-ink/55 mb-1">Target (no HP / email)</label>
            <input type="text" name="target" required class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm mb-3" placeholder="08xxx atau test@email.com">
            <button class="w-full px-4 py-2 bg-ink text-cream rounded-xl text-sm font-semibold hover:brightness-95">Kirim Test</button>
            <p class="text-xs text-ink/55 mt-3">Hasil dicatat di Log Notifikasi termasuk response provider.</p>
        </form>

        <div class="bg-cream-deep/40 border border-cream-deep/70 rounded-2xl p-4 text-xs text-ink/65 leading-relaxed">
            <strong class="text-ink">Setup .env:</strong>
            <pre class="mt-2 text-[11px] bg-white p-2 rounded font-mono overflow-x-auto">WA_PROVIDER=fonnte
FONNTE_TOKEN=xxx

# atau pakai WA Business API resmi:
# WA_PROVIDER=cloud_api
# WA_CLOUD_TOKEN=...
# WA_CLOUD_PHONE_NUMBER_ID=...

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ahnet.id
MAIL_FROM_NAME="AHNet"</pre>
        </div>
    </div>
</div>
@endsection
