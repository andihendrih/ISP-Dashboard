<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR Sheet · Inventory</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        @page { size: A4 portrait; margin: 1cm; }
        body { font-family: system-ui, sans-serif; background: #f4ecd8; }
        .sheet {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.4cm;
        }
        .sticker {
            border: 1px dashed #bbb;
            border-radius: 0.3cm;
            padding: 0.25cm;
            background: #fff;
            height: 3.3cm;
            display: grid;
            grid-template-columns: 2.4cm 1fr;
            gap: 0.2cm;
            align-items: center;
            break-inside: avoid;
        }
        .sticker canvas { width: 2.4cm !important; height: 2.4cm !important; }
        .brand-dot {
            width: .4cm; height: .4cm; background: #1a1a1a; color: #f5c542;
            border-radius: .1cm; display: inline-flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .28cm; vertical-align: middle;
        }
        .serial { font-family: ui-monospace, monospace; font-weight: 700; font-size: .35cm; word-break: break-all; line-height: 1.15; }
        .meta { font-size: .26cm; color: #444; margin-top: .1cm; line-height: 1.2; }
        .mac { font-family: ui-monospace, monospace; font-size: .24cm; color: #777; }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .sticker { border-color: #aaa; }
            .sheet { page-break-after: always; }
        }
    </style>
</head>
<body class="p-6">
    <div class="no-print max-w-4xl mx-auto mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h1 class="text-lg font-bold">QR Sheet · {{ $devices->count() }} sticker</h1>
            <p class="text-xs text-slate-500">Layout A4 3×8 = 24 sticker/lembar. Cetak → potong per sticker → tempel di perangkat.</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-black text-white rounded-xl text-sm">🖨 Cetak</button>
            <a href="{{ route('devices.index') }}" class="px-4 py-2 border border-slate-300 rounded-xl text-sm">Kembali</a>
        </div>
    </div>

    @if($devices->isEmpty())
        <div class="max-w-4xl mx-auto bg-white rounded-2xl p-8 text-center text-slate-500">
            Tidak ada perangkat yang cocok. Gunakan filter atau tambah perangkat dulu.
        </div>
    @else
        <div class="sheet">
            @foreach($devices as $i => $d)
                <div class="sticker">
                    <canvas id="qr-{{ $d->id }}"></canvas>
                    <div>
                        <div><span class="brand-dot">A</span> <span style="font-weight:700; font-size:.32cm;">AHNet</span></div>
                        <div class="serial">{{ $d->serial_number }}</div>
                        <div class="meta">{{ $d->typeLabel() }} · {{ $d->brand ?: '' }} {{ $d->model ?: '' }}</div>
                        @if($d->mac_address)
                            <div class="mac">{{ $d->mac_address }}</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <script>
        const BASE = @json($base);
        const DEVICES = @json($devices->map(fn($d) => ['id' => $d->id, 'serial' => $d->serial_number])->values());
        DEVICES.forEach(d => {
            QRCode.toCanvas(document.getElementById('qr-' + d.id), BASE + '/' + encodeURIComponent(d.serial), {
                width: 96, margin: 1, errorCorrectionLevel: 'M',
                color: { dark: '#1a1a1a', light: '#ffffff' }
            });
        });
    </script>
</body>
</html>
