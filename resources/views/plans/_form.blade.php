@php($p = $plan)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <div>
        <label class="text-sm font-medium">Kode</label>
        <input name="code" required value="{{ old('code', $p?->code) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
    </div>
    <div>
        <label class="text-sm font-medium">Nama</label>
        <input name="name" required value="{{ old('name', $p?->name) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="text-sm font-medium">Tipe</label>
        <select name="service_type" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
            <option value="pppoe"   @selected(old('service_type', $p?->service_type ?? 'pppoe') === 'pppoe')>PPPoE</option>
            <option value="hotspot" @selected(old('service_type', $p?->service_type) === 'hotspot')>Hotspot</option>
        </select>
    </div>
    <div>
        <label class="text-sm font-medium">Rate Limit (Mikrotik)</label>
        <input name="rate_limit" placeholder="10M/10M" value="{{ old('rate_limit', $p?->rate_limit) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="text-sm font-medium">Group RADIUS</label>
        <input name="radius_group" placeholder="pppoe-10mbps" value="{{ old('radius_group', $p?->radius_group) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2 font-mono">
    </div>
    <div>
        <label class="text-sm font-medium">Harga / bulan (Rp)</label>
        <input name="price" type="number" step="0.01" min="0" required value="{{ old('price', $p?->price ?? 0) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
    </div>
    <div>
        <label class="text-sm font-medium">PPN (%)</label>
        <input name="tax_percent" type="number" step="0.01" min="0" max="100" value="{{ old('tax_percent', $p?->tax_percent ?? 0) }}" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">
    </div>
    <div class="flex items-end">
        <label class="inline-flex items-center gap-2 mt-1">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $p?->is_active ?? true)) class="rounded border-ink/20">
            <span class="text-sm">Aktif</span>
        </label>
    </div>
    <div class="col-span-2">
        <label class="text-sm font-medium">Deskripsi</label>
        <textarea name="description" rows="3" class="w-full mt-1 border border-ink/10 rounded-lg px-3 py-2">{{ old('description', $p?->description) }}</textarea>
    </div>
</div>
