@php
    $creds = $setting->waCreds();
    $fonnte = $creds['fonnte'] ?? [];
    $cloud  = $creds['cloudapi'] ?? [];
@endphp

<h2 class="font-bold text-sm mb-4">Provider WhatsApp</h2>
<form method="POST" action="{{ route('settings.tenant_settings.whatsapp.update') }}" class="space-y-3">
    @csrf
    <div>
        <label class="block text-xs font-semibold text-ink/65 mb-1">Provider Aktif</label>
        <select name="wa_provider" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm" onchange="document.getElementById('wa-fonnte').classList.toggle('hidden', this.value !== 'fonnte'); document.getElementById('wa-cloudapi').classList.toggle('hidden', this.value !== 'cloudapi');">
            <option value="null" {{ $setting->wa_provider === 'null' ? 'selected' : '' }}>Tidak aktif (Null)</option>
            <option value="fonnte" {{ $setting->wa_provider === 'fonnte' ? 'selected' : '' }}>Fonnte</option>
            <option value="cloudapi" {{ $setting->wa_provider === 'cloudapi' ? 'selected' : '' }}>WhatsApp Cloud API (Meta)</option>
        </select>
    </div>

    <div id="wa-fonnte" class="{{ $setting->wa_provider === 'fonnte' ? '' : 'hidden' }} space-y-3 bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60">
        <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">Fonnte Credentials</div>
        <div>
            <label class="block text-xs text-ink/55 mb-1">API Token</label>
            <input type="password" name="fonnte_token" value="{{ old('fonnte_token', $fonnte['token'] ?? '') }}" placeholder="kosongkan kalau gak ganti" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            <p class="text-xs text-ink/45 mt-1">Daftar di <a href="https://fonnte.com" target="_blank" class="underline">fonnte.com</a> → Dashboard → API Token.</p>
        </div>
    </div>

    <div id="wa-cloudapi" class="{{ $setting->wa_provider === 'cloudapi' ? '' : 'hidden' }} space-y-3 bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60">
        <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">WhatsApp Cloud API (Meta)</div>
        <div>
            <label class="block text-xs text-ink/55 mb-1">Access Token</label>
            <input type="password" name="cloudapi_token" value="{{ old('cloudapi_token', $cloud['token'] ?? '') }}" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-ink/55 mb-1">Phone Number ID</label>
                <input name="cloudapi_phone_id" value="{{ old('cloudapi_phone_id', $cloud['phone_id'] ?? '') }}" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Graph API Version</label>
                <input name="cloudapi_version" value="{{ old('cloudapi_version', $cloud['version'] ?? 'v18.0') }}" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
        </div>
        <p class="text-xs text-ink/45">Setup di <a href="https://developers.facebook.com/apps" target="_blank" class="underline">Meta for Developers</a>.</p>
    </div>

    <div class="pt-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Simpan WA</button>
    </div>
</form>

<div class="mt-4 pt-4 border-t border-cream-deep/60">
    <h3 class="font-bold text-sm mb-2">Test Kirim WA</h3>
    <p class="text-xs text-ink/55 mb-2">Kirim pesan test ke nomor WhatsApp untuk verifikasi credential di atas berfungsi.</p>
    <form method="POST" action="{{ route('settings.tenant_settings.whatsapp.test') }}" class="flex gap-2 flex-wrap">
        @csrf
        <input name="phone" placeholder="08xxx atau 62xxx" class="flex-1 min-w-[200px] border border-ink/10 rounded-lg px-3 py-2 text-sm">
        <button class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm hover:bg-emerald-700">Kirim Test</button>
    </form>
</div>
