@extends('layouts.app')
@section('title','Scan QR Perangkat')
@section('breadcrumb','Inventory / Scan')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.10/html5-qrcode.min.js"></script>
@endpush

@section('content')
<div class="max-w-xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold">Scan QR Perangkat</h1>
            <p class="text-sm text-ink/55">Arahkan kamera ke QR sticker perangkat. Akan otomatis redirect ke detail.</p>
        </div>
        <a href="{{ route('devices.index') }}" class="text-sm text-ink/55 hover:underline">&larr; Kembali</a>
    </div>

    <div id="reader" class="rounded-3xl overflow-hidden shadow-card bg-black aspect-square"></div>

    <div id="status" class="mt-4 text-sm text-center text-ink/55">Menunggu izin kamera...</div>

    <div class="mt-4 bg-white rounded-3xl shadow-card p-4">
        <div class="text-xs text-ink/55 font-medium mb-2">Atau input manual serial:</div>
        <form id="manual-form" class="flex gap-2">
            <input type="text" id="manual-serial" placeholder="Contoh: ZT3HGU8A..." class="flex-1 border border-ink/10 rounded-xl text-sm px-3 py-2 bg-white font-mono">
            <button class="px-4 py-2 bg-ink text-white rounded-xl text-sm">Cari</button>
        </form>
    </div>
</div>

<script>
    const status = document.getElementById('status');
    const baseUrl = @json(url('/d'));

    function gotoDevice(code) {
        status.textContent = '✓ Redirect ke ' + code + '...';
        // Kalau QR-nya URL penuh (/d/SERIAL), pakai langsung. Kalau cuma serial, bikin URL-nya.
        let target;
        try {
            const u = new URL(code);
            target = u.href;
        } catch (_) {
            target = baseUrl + '/' + encodeURIComponent(code);
        }
        window.location.href = target;
    }

    document.getElementById('manual-form').addEventListener('submit', e => {
        e.preventDefault();
        const v = document.getElementById('manual-serial').value.trim();
        if (v) gotoDevice(v);
    });

    // Init scanner
    (async () => {
        try {
            const scanner = new Html5Qrcode("reader");
            await scanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (decoded) => {
                    scanner.stop();
                    gotoDevice(decoded);
                },
                () => {} // scan error (tiap frame), ignore
            );
            status.textContent = 'Siap scan... arahkan QR ke kotak.';
        } catch (e) {
            status.textContent = '⚠ Gagal buka kamera: ' + (e?.message || e) + '. Gunakan input manual di bawah.';
            status.className = 'mt-4 text-sm text-center text-rose-700';
        }
    })();
</script>
@endsection
