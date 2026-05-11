<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>QR · {{ $device->serial_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        @page { margin: 0.5cm; }
        body { font-family: system-ui, sans-serif; background: #f4ecd8; }
        .sticker {
            width: 9cm; height: 6cm;
            border: 2px dashed #d4c69e;
            border-radius: 0.5cm;
            padding: 0.4cm;
            background: #fff;
            display: grid;
            grid-template-columns: 4.2cm 1fr;
            gap: 0.3cm;
            align-items: center;
        }
        .sticker canvas { width: 4cm !important; height: 4cm !important; }
        .brand-bar { display: flex; align-items: center; gap: .3cm; margin-bottom: .25cm; }
        .brand-dot {
            width: .8cm; height: .8cm; background: #1a1a1a; color: #f5c542;
            border-radius: .2cm; display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: .6cm;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .no-print { display: none !important; }
            .sticker { border-color: #999; box-shadow: none; }
        }
    </style>
</head>
<body class="p-6">
    <div class="no-print max-w-2xl mx-auto mb-4 flex items-center justify-between">
        <h1 class="text-lg font-bold">QR Label · <span class="font-mono">{{ $device->serial_number }}</span></h1>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-black text-white rounded-xl text-sm">🖨 Cetak</button>
            <a href="{{ route('devices.show', $device) }}" class="px-4 py-2 border border-slate-300 rounded-xl text-sm">Kembali</a>
        </div>
    </div>

    <div class="flex justify-center">
        <div class="sticker">
            <canvas id="qr"></canvas>
            <div>
                <div class="brand-bar">
                    <div class="brand-dot">A</div>
                    <div>
                        <div style="font-weight:700; font-size: .45cm;">AHNet</div>
                        <div style="font-size: .28cm; color:#666;">Monitoring &amp; Support</div>
                    </div>
                </div>
                <div style="font-family: ui-monospace, monospace; font-weight: 700; font-size: .42cm; word-break: break-all;">{{ $device->serial_number }}</div>
                <div style="font-size: .32cm; color:#333; margin-top: .15cm;">
                    {{ $device->typeLabel() }} · {{ $device->brand ?: '' }} {{ $device->model ?: '' }}
                </div>
                @if($device->mac_address)
                    <div style="font-family: ui-monospace, monospace; font-size: .28cm; color:#666; margin-top: .1cm;">MAC: {{ $device->mac_address }}</div>
                @endif
                <div style="font-size: .26cm; color:#888; margin-top: .25cm;">Scan untuk buka info perangkat</div>
            </div>
        </div>
    </div>

    <script>
        QRCode.toCanvas(document.getElementById('qr'), @json($url), {
            width: 160,
            margin: 1,
            errorCorrectionLevel: 'M',
            color: { dark: '#1a1a1a', light: '#ffffff' }
        });
    </script>
</body>
</html>
