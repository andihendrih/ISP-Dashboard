@php
    $creds = $setting->emailCreds();
    $smtp = $creds['smtp'] ?? [];
    $from = $creds['from'] ?? [];
    $sendgrid = $creds['sendgrid'] ?? [];
    $mailgun = $creds['mailgun'] ?? [];
@endphp

<h2 class="font-bold text-sm mb-4">Provider Email Platform</h2>
<p class="text-xs text-ink/55 mb-3">Provider email yang lo pakai untuk kirim invoice tagihan ke tenant PIC. <code>from</code> address akan kelihatan sebagai pengirim.</p>
<form method="POST" action="{{ route('admin.platform_settings.email.update') }}" class="space-y-3">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Provider</label>
            <select name="email_provider" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm" onchange="['smtp','sendgrid','mailgun'].forEach(p=>{document.getElementById('em-'+p).classList.toggle('hidden', this.value !== p)});">
                <option value="null" {{ $setting->email_provider === 'null' ? 'selected' : '' }}>Tidak aktif (Null)</option>
                <option value="smtp" {{ $setting->email_provider === 'smtp' ? 'selected' : '' }}>SMTP</option>
                <option value="sendgrid" {{ $setting->email_provider === 'sendgrid' ? 'selected' : '' }}>SendGrid</option>
                <option value="mailgun" {{ $setting->email_provider === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">From Email</label>
            <input type="email" name="from_email" value="{{ old('from_email', $from['email'] ?? '') }}" placeholder="billing@brandtenant.com" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">From Name</label>
            <input name="from_name" value="{{ old('from_name', $from['name'] ?? '') }}" placeholder="PT Padi Net" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        </div>
    </div>

    <div id="em-smtp" class="{{ $setting->email_provider === 'smtp' ? '' : 'hidden' }} bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60 space-y-3">
        <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">SMTP</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-ink/55 mb-1">Host</label>
                <input name="smtp_host" value="{{ old('smtp_host', $smtp['host'] ?? '') }}" placeholder="smtp.gmail.com" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Port</label>
                <input type="number" name="smtp_port" value="{{ old('smtp_port', $smtp['port'] ?? 587) }}" min="1" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Encryption</label>
                <select name="smtp_encryption" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                    @foreach(['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'] as $val => $lbl)
                        <option value="{{ $val }}" {{ ($smtp['encryption'] ?? 'tls') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-ink/55 mb-1">Username</label>
                <input name="smtp_username" value="{{ old('smtp_username', $smtp['username'] ?? '') }}" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Password (kosong = gak ganti)</label>
                <input type="password" name="smtp_password" placeholder="••••••••" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
            </div>
        </div>
    </div>

    <div id="em-sendgrid" class="{{ $setting->email_provider === 'sendgrid' ? '' : 'hidden' }} bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60 space-y-3">
        <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">SendGrid</div>
        <div>
            <label class="block text-xs text-ink/55 mb-1">API Key (kosong = gak ganti)</label>
            <input type="password" name="sendgrid_api_key" placeholder="SG.xxx" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
        </div>
    </div>

    <div id="em-mailgun" class="{{ $setting->email_provider === 'mailgun' ? '' : 'hidden' }} bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60 space-y-3">
        <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">Mailgun</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-ink/55 mb-1">Domain</label>
                <input name="mailgun_domain" value="{{ old('mailgun_domain', $mailgun['domain'] ?? '') }}" placeholder="mg.brandtenant.com" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Secret (kosong = gak ganti)</label>
                <input type="password" name="mailgun_secret" placeholder="key-xxxxx" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
        </div>
    </div>

    <div class="pt-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Simpan Email</button>
    </div>
</form>

<div class="mt-4 pt-4 border-t border-cream-deep/60">
    <h3 class="font-bold text-sm mb-2">Test Kirim Email</h3>
    <p class="text-xs text-ink/55 mb-2">Kirim email test untuk verifikasi credential SMTP/SendGrid/Mailgun di atas berfungsi.</p>
    <form method="POST" action="{{ route('admin.platform_settings.email.test') }}" class="flex gap-2 flex-wrap">
        @csrf
        <input name="email" type="email" placeholder="test@example.com" class="flex-1 min-w-[200px] border border-ink/10 rounded-lg px-3 py-2 text-sm">
        <button class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm hover:bg-emerald-700">Kirim Test</button>
    </form>
</div>
