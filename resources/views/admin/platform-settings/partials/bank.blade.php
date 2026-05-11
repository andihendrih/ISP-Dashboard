<h2 class="font-bold text-sm mb-4">Bank Info Platform</h2>
<p class="text-xs text-ink/55 mb-4">Rekening tempat tenant transfer ke lo. Bakal muncul di PDF invoice tenant billing.</p>
<form method="POST" action="{{ route('admin.platform_settings.bank.update') }}" class="space-y-3">
    @csrf
    <div>
        <label class="block text-xs font-semibold text-ink/65 mb-1">Daftar Rekening (multi-line)</label>
        <textarea name="bank_info" rows="6" class="w-full border border-ink/10 rounded-lg px-3 py-2 text-sm font-mono" placeholder="BCA|1234567890|PT AHNet Mandiri&#10;Mandiri|0987654321|PT AHNet Mandiri&#10;BNI|5556667770|PT AHNet Mandiri">{{ old('bank_info', $setting->bank_info) }}</textarea>
        <p class="text-xs text-ink/45 mt-1">Format per baris: <code>BANK|NO_REKENING|NAMA</code>. Setiap baris = 1 rekening.</p>
    </div>
    <div class="pt-2">
        <button class="px-5 py-2 bg-ink text-white rounded-xl shadow-card hover:bg-black text-sm">Simpan Bank Info</button>
    </div>
</form>

@if(!empty($setting->bankAccounts()))
<div class="mt-6 pt-4 border-t border-cream-deep/60">
    <h3 class="font-bold text-sm mb-2">Preview Parse</h3>
    <ul class="space-y-1 text-sm">
        @foreach($setting->bankAccounts() as $b)
            <li class="font-mono">• <strong>{{ $b['bank'] }}</strong> {{ $b['account'] }} a/n {{ $b['name'] }}</li>
        @endforeach
    </ul>
</div>
@endif
