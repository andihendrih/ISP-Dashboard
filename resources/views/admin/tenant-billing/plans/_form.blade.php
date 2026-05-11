@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-xs font-semibold text-ink/65 mb-1">Code (slug)</label>
        <input name="code" value="{{ old('code', $plan->code ?? '') }}" required pattern="[a-z0-9-]+" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm" placeholder="basic / pro / enterprise / custom-xxx">
        @error('code')<p class="text-xs text-rose-700 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-xs font-semibold text-ink/65 mb-1">Nama</label>
        <input name="name" value="{{ old('name', $plan->name ?? '') }}" required class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm">
        @error('name')<p class="text-xs text-rose-700 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-xs font-semibold text-ink/65 mb-1">Harga (IDR/bulan)</label>
        <input type="number" name="price" value="{{ old('price', $plan->price ?? 0) }}" min="0" required class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
    </div>
    <div>
        <label class="block text-xs font-semibold text-ink/65 mb-1">Sort Order</label>
        <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order ?? 0) }}" min="0" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm">
    </div>
    <div>
        <label class="block text-xs font-semibold text-ink/65 mb-1">Max Pelanggan</label>
        <input type="number" name="max_customers" value="{{ old('max_customers', $plan->max_customers ?? '') }}" min="0" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm" placeholder="kosong = unlimited">
    </div>
    <div>
        <label class="block text-xs font-semibold text-ink/65 mb-1">Max Perangkat</label>
        <input type="number" name="max_devices" value="{{ old('max_devices', $plan->max_devices ?? '') }}" min="0" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-sm" placeholder="kosong = unlimited">
    </div>
    <div class="md:col-span-2">
        <label class="block text-xs font-semibold text-ink/65 mb-1">Features (JSON)</label>
        <textarea name="features_json" rows="4" class="w-full border border-ink/10 rounded-lg px-3 py-2 font-mono text-xs" placeholder='{"support_priority":"standard","white_label":false}'>{{ old('features_json', isset($plan) && $plan->features ? json_encode($plan->features, JSON_PRETTY_PRINT) : '') }}</textarea>
        <p class="text-xs text-ink/45 mt-1">Format JSON object, untuk extend metadata plan.</p>
    </div>
    <div class="md:col-span-2 flex items-center gap-2">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $plan->is_active ?? true) ? 'checked' : '' }}>
        <label for="is_active" class="text-sm">Plan aktif (boleh dipilih saat subscribe tenant)</label>
    </div>
</div>
