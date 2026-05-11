<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Cetak Voucher · {{ $batch->profile }}</title>
<style>
    *,*::before,*::after { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #f5f5f5; font-family: -apple-system, "Segoe UI", system-ui, sans-serif; }

    .toolbar {
        position: sticky; top: 0; z-index: 10;
        background: #1a1a1a; color: #fff;
        padding: 10px 16px; display: flex; gap: 10px; align-items: center; justify-content: space-between;
    }
    .toolbar a, .toolbar button {
        background: #f5c542; color: #1a1a1a; padding: 6px 14px; border-radius: 8px;
        border: 0; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none;
    }
    .toolbar .meta { font-size: 12px; opacity: 0.8; }

    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    .sheet {
        width: 210mm;
        min-height: 297mm;
        padding: 8mm;
        margin: 16px auto;
        background: #fff;
        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        page-break-after: always;
    }
    .sheet:last-child { page-break-after: auto; }

    .grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        grid-template-rows: repeat(6, 1fr);
        gap: 2mm;
        height: calc(297mm - 16mm);
    }

    .voucher {
        border: 1px dashed #1a1a1a;
        padding: 2.5mm;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        page-break-inside: avoid;
        background: #fff;
    }
    .voucher .brand {
        font-size: 9pt;
        font-weight: 800;
        letter-spacing: 0.5px;
        line-height: 1;
    }
    .voucher .tag {
        font-size: 6pt;
        color: #6b7280;
        line-height: 1.1;
    }
    .voucher .profile {
        font-size: 7.5pt;
        font-weight: 700;
        background: #f5c542;
        color: #1a1a1a;
        padding: 1mm 2mm;
        border-radius: 2mm;
        text-align: center;
        margin: 1.5mm 0;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .voucher .code {
        font-family: "Courier New", monospace;
        font-size: 14pt;
        font-weight: 800;
        text-align: center;
        letter-spacing: 1.2px;
        background: #fff;
        border: 1.5px solid #1a1a1a;
        padding: 1.5mm 0.5mm;
        border-radius: 1mm;
    }
    .voucher .footer {
        font-size: 5.5pt;
        color: #6b7280;
        line-height: 1.2;
        margin-top: 1mm;
    }
    .voucher .footer .expires {
        color: #b91c1c;
        font-weight: 700;
        margin-top: 0.5mm;
    }

    @media print {
        body { background: #fff; }
        .toolbar { display: none !important; }
        .sheet  { box-shadow: none; margin: 0; padding: 8mm; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <div>
        <strong>Cetak Voucher</strong>
        <span class="meta">· {{ $batch->profile }} · {{ $batch->count }} voucher · batch {{ $batch->label ?? '#'.$batch->id }}</span>
    </div>
    <div style="display:flex; gap:8px;">
        <a href="{{ route('vouchers.generate-form') }}">← Kembali</a>
        <button onclick="window.print()">🖨 Cetak Sekarang</button>
    </div>
</div>

@php
    $brand    = config('ahnet.brand_short', config('app.name', 'AHNet'));
    $tagline  = config('ahnet.outlet_tagline', 'INTERNET SERVICE PROVIDER');
    $cs       = config('ahnet.cs_phone', '');
    $codes    = $batch->codes ?? [];
    $perSheet = 30;
    $sheets   = array_chunk($codes, $perSheet);
    $duration = preg_replace('/^(Hotspot|HS_)/', '', $batch->profile);
    $duration = preg_replace_callback('/(\d+)([A-Za-z]+)/', function ($m) {
        $unit = strtolower($m[2]);
        $map = ['jam'=>'Jam', 'hari'=>'Hari', 'minggu'=>'Minggu', 'menit'=>'Menit', 'bulan'=>'Bulan'];
        return $m[1] . ' ' . ($map[$unit] ?? ucfirst($unit));
    }, $duration);
@endphp

@foreach($sheets as $sheetCodes)
    <div class="sheet">
        <div class="grid">
            @foreach($sheetCodes as $code)
                <div class="voucher">
                    <div>
                        <div class="brand">{{ strtoupper($brand) }}</div>
                        <div class="tag">{{ $tagline }}</div>
                        <div class="profile">{{ $duration }}</div>
                    </div>
                    <div class="code">{{ $code }}</div>
                    <div class="footer">
                        Login wifi → masukkan kode di atas.
                        @if($batch->expires_at)
                            <div class="expires">Berlaku s/d {{ $batch->expires_at->format('d M Y') }}</div>
                        @endif
                        @if($cs) <div>CS: {{ $cs }}</div> @endif
                    </div>
                </div>
            @endforeach
            {{-- Fill empty slots --}}
            @for($i = count($sheetCodes); $i < $perSheet; $i++)
                <div></div>
            @endfor
        </div>
    </div>
@endforeach

<script>
    // Auto-trigger print kalau ada ?auto=1
    if (new URLSearchParams(window.location.search).get('auto') === '1') {
        window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    }
</script>
</body>
</html>
