<h2 class="font-bold text-sm mb-4">Brand Platform</h2>
<p class="text-xs text-ink/55 mb-4">Brand ini muncul di invoice yang lo terbitkan ke tenant (SUB-xxxx). Bedanya sama "Settings Tenant" — itu untuk tenant nagih customer-nya; ini untuk <strong>lo nagih tenant</strong>.</p>
<form method="POST" action="{{ route('admin.platform_settings.brand.update') }}" enctype="multipart/form-data" class="space-y-3">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Nama Platform</label>
            <input name="brand_name" value="{{ old('brand_name', $setting->brand_name) }}" placeholder="AHNet SaaS Platform" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-ink/65 mb-1">Email Platform</label>
            <input name="brand_email" value="{{ old('brand_email', $setting->brand_email) }}" type="email" placeholder="billing@ahnet.biz.id" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
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
            <label class="block text-xs font-semibold text-ink/65 mb-1">Tagline (opsional)</label>
            <input name="brand_tagline" value="{{ old('brand_tagline', $setting->brand_tagline) }}" placeholder="SaaS billing untuk ISP" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-ink/65 mb-1">Logo Platform (PNG/JPG/SVG, max 2MB)</label>
            <input type="file" name="brand_logo" accept=".png,.jpg,.jpeg,.svg" class="w-full text-xs">
            @if($setting->brand_logo_path)
                <div class="mt-2 flex items-center gap-2">
                    <img src="{{ asset('storage/'.$setting->brand_logo_path) }}" alt="Logo" class="h-10 w-10 object-contain bg-cream-deep/40 rounded-lg">
                    <span class="text-xs text-ink/55">Logo saat ini</span>
                </div>
            @endif
        </div>
    </div>
    <div class="pt-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Simpan Brand Platform</button>
    </div>
</form>
