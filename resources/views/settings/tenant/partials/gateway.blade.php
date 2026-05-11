@php
    $creds = $setting->paymentCreds();
    $midtrans = $creds['midtrans'] ?? [];
    $xendit   = $creds['xendit']   ?? [];
    $tripay   = $creds['tripay']   ?? [];
    $manualBanks = $creds['manual']['bank_accounts'] ?? [];
    $manualText = '';
    foreach ($manualBanks as $b) {
        $manualText .= ($b['bank'] ?? '') . '|' . ($b['account'] ?? '') . '|' . ($b['name'] ?? '') . "\n";
    }
    $manualText = trim($manualText);
@endphp

<h2 class="font-bold text-sm mb-4">Payment Gateway</h2>
<form method="POST" action="{{ route('settings.tenant_settings.gateway.update') }}" class="space-y-3">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Default Gateway</label>
            <select name="payment_gateway_default" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
                @foreach(['manual'=>'Manual Transfer','midtrans'=>'Midtrans','xendit'=>'Xendit','tripay'=>'Tripay'] as $val=>$lbl)
                    <option value="{{ $val }}" {{ $setting->payment_gateway_default === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                @endforeach
            </select>
            <p class="text-xs text-ink/45 mt-1">Gateway yang dipakai by default saat tagih customer tenant.</p>
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Gateway Aktif</label>
            <div class="grid grid-cols-2 gap-1 text-sm">
                <label class="flex items-center gap-2"><input type="hidden" name="payment_manual_enabled" value="0"><input type="checkbox" name="payment_manual_enabled" value="1" {{ $setting->payment_manual_enabled ? 'checked' : '' }}> Manual</label>
                <label class="flex items-center gap-2"><input type="hidden" name="payment_midtrans_enabled" value="0"><input type="checkbox" name="payment_midtrans_enabled" value="1" {{ $setting->payment_midtrans_enabled ? 'checked' : '' }}> Midtrans</label>
                <label class="flex items-center gap-2"><input type="hidden" name="payment_xendit_enabled" value="0"><input type="checkbox" name="payment_xendit_enabled" value="1" {{ $setting->payment_xendit_enabled ? 'checked' : '' }}> Xendit</label>
                <label class="flex items-center gap-2"><input type="hidden" name="payment_tripay_enabled" value="0"><input type="checkbox" name="payment_tripay_enabled" value="1" {{ $setting->payment_tripay_enabled ? 'checked' : '' }}> Tripay</label>
            </div>
        </div>
    </div>

    {{-- Manual --}}
    <div class="bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60 space-y-3">
        <div class="flex items-center justify-between">
            <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">Manual Transfer — Daftar Rekening</div>
        </div>
        <textarea name="manual_bank_accounts" rows="4" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-xs" placeholder="BCA|1234567890|PT Padi Net Mandiri&#10;Mandiri|0987654321|PT Padi Net Mandiri">{{ old('manual_bank_accounts', $manualText) }}</textarea>
        <p class="text-xs text-ink/45">Format per baris: <code>BANK|NO_REKENING|NAMA</code>. Akan muncul di invoice customer tenant.</p>
    </div>

    {{-- Midtrans --}}
    <div class="bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60 space-y-3">
        <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">Midtrans</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-ink/55 mb-1">Server Key (kosong = gak ganti)</label>
                <input type="password" name="midtrans_server_key" placeholder="SB-Mid-server-xxx" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Client Key (kosong = gak ganti)</label>
                <input type="password" name="midtrans_client_key" placeholder="SB-Mid-client-xxx" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Merchant ID</label>
                <input name="midtrans_merchant_id" value="{{ old('midtrans_merchant_id', $midtrans['merchant_id'] ?? '') }}" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="midtrans_is_production" value="0">
                    <input type="checkbox" name="midtrans_is_production" value="1" {{ ($midtrans['is_production'] ?? false) ? 'checked' : '' }}>
                    Production mode
                </label>
            </div>
        </div>
        <p class="text-xs text-ink/45">Daftar di <a href="https://dashboard.midtrans.com" target="_blank" class="underline">dashboard.midtrans.com</a>.</p>
    </div>

    {{-- Xendit --}}
    <div class="bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60 space-y-3">
        <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">Xendit</div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <label class="block text-xs text-ink/55 mb-1">Secret Key (kosong = gak ganti)</label>
                <input type="password" name="xendit_secret_key" placeholder="xnd_development_xxx" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Callback Token (webhook verification)</label>
                <input type="password" name="xendit_callback_token" placeholder="kosong = gak ganti" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
        </div>
        <p class="text-xs text-ink/45">Daftar di <a href="https://dashboard.xendit.co" target="_blank" class="underline">dashboard.xendit.co</a>.</p>
    </div>

    {{-- Tripay --}}
    <div class="bg-cream-deep/30 rounded-2xl p-4 border border-cream-deep/60 space-y-3">
        <div class="text-xs font-semibold text-ink/65 uppercase tracking-wide">Tripay</div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs text-ink/55 mb-1">API Key</label>
                <input type="password" name="tripay_api_key" placeholder="kosong = gak ganti" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Private Key</label>
                <input type="password" name="tripay_private_key" placeholder="kosong = gak ganti" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
            <div>
                <label class="block text-xs text-ink/55 mb-1">Merchant Code</label>
                <input name="tripay_merchant_code" value="{{ old('tripay_merchant_code', $tripay['merchant_code'] ?? '') }}" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
            </div>
        </div>
        <p class="text-xs text-ink/45">Daftar di <a href="https://tripay.co.id" target="_blank" class="underline">tripay.co.id</a>.</p>
    </div>

    <div class="pt-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Simpan Gateway</button>
    </div>
</form>
