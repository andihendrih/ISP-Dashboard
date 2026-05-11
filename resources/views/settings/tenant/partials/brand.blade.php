<h2 class="font-bold text-sm mb-4">Brand Tenant</h2>
<form method="POST" action="{{ route('settings.tenant_settings.brand.update') }}" enctype="multipart/form-data" class="space-y-3">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Nama PT/Brand</label>
            <input name="brand_name" value="{{ old('brand_name', $setting->brand_name) }}" placeholder="PT Padi Net Mandiri" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">NPWP</label>
            <input name="brand_npwp" value="{{ old('brand_npwp', $setting->brand_npwp) }}" placeholder="00.000.000.0-000.000" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-ink/65 mb-1">Alamat</label>
            <input name="brand_address" value="{{ old('brand_address', $setting->brand_address) }}" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Telepon</label>
            <input name="brand_phone" value="{{ old('brand_phone', $setting->brand_phone) }}" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Email Brand</label>
            <input name="brand_email" value="{{ old('brand_email', $setting->brand_email) }}" type="email" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-ink/65 mb-1">Logo Brand (PNG/JPG/SVG, max 2MB)</label>
            <input type="file" name="brand_logo" accept=".png,.jpg,.jpeg,.svg" class="w-full text-xs">
            @if($setting->brand_logo_path)
                <div class="mt-2 flex items-center gap-2">
                    <img src="{{ asset('storage/'.$setting->brand_logo_path) }}" alt="Logo" class="h-10 w-10 object-contain bg-cream-deep/40 rounded-lg">
                    <span class="text-xs text-ink/55">Logo saat ini</span>
                </div>
            @endif
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-ink/65 mb-1">Info Rekening / Pembayaran (multi-line, muncul di invoice)</label>
            <textarea name="brand_bank_info" rows="4" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm font-mono" placeholder="BCA 1234567890 a/n PT Padi Net Mandiri&#10;Mandiri 0987654321 a/n PT Padi Net Mandiri">{{ old('brand_bank_info', $setting->brand_bank_info) }}</textarea>
        </div>
    </div>
    <div class="pt-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Simpan Brand</button>
    </div>
</form>
