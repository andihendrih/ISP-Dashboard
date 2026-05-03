@extends('layouts.app')
@section('title','Map Pelanggan')

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<style>
    #map { height: calc(100vh - 280px); min-height: 380px; border-radius: 16px; }
    @media (max-width: 640px) {
        #map { height: 60vh; min-height: 320px; }
    }
    .legend-pill { display:inline-flex; gap:6px; align-items:center; font-size:12px; }
    .legend-pill .dot { width:10px; height:10px; border-radius:50%; display:inline-block; }
    .leaflet-popup-content { font-family: inherit; }
</style>
@endpush

@section('content')
<div class="flex items-start justify-between gap-3 mb-4 flex-wrap">
    <div>
        <h1 class="text-xl sm:text-2xl font-bold">Map Pelanggan</h1>
        <p class="text-sm text-ink/55">Visualisasi sebaran pelanggan berdasarkan koordinat (latitude/longitude).</p>
    </div>
    <div class="flex gap-2 text-xs text-ink/65 flex-wrap">
        <span class="legend-pill"><span class="dot" style="background:#10b981"></span>Aktif</span>
        <span class="legend-pill"><span class="dot" style="background:#ef4444"></span>Isolir</span>
        <span class="legend-pill"><span class="dot" style="background:#9ca3af"></span>Inactive</span>
        <span class="legend-pill"><span class="dot" style="background:#3b82f6"></span>Free</span>
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 mb-4">
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55">Total Pelanggan</div>
        <div class="text-3xl font-extrabold mt-1">{{ number_format($totalAll) }}</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55">Sudah Di-Pin</div>
        <div class="text-3xl font-extrabold mt-1 text-emerald-700">{{ number_format($totalGeo) }}</div>
    </div>
    <div class="bg-white border border-cream-deep/60 rounded-2xl p-4">
        <div class="text-xs text-ink/55">Belum Di-Pin</div>
        <div class="text-3xl font-extrabold mt-1 text-rose-700">{{ number_format($totalNoGeo) }}</div>
    </div>
</div>

<div class="bg-white border border-cream-deep/60 rounded-2xl p-3 mb-4 flex gap-2 items-end flex-wrap">
    <div>
        <label class="text-xs text-ink/55">Status</label>
        <select id="f-status" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
            <option value="">Semua</option>
            <option value="active">Aktif</option>
            <option value="isolir">Isolir</option>
            <option value="inactive">Inactive</option>
            <option value="free">Free</option>
        </select>
    </div>
    <div>
        <label class="text-xs text-ink/55">Tipe Layanan</label>
        <select id="f-service-type" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
            <option value="">Semua</option>
            <option value="pppoe">PPPoE</option>
            <option value="hotspot">Hotspot</option>
        </select>
    </div>
    <div>
        <label class="text-xs text-ink/55">Paket</label>
        <select id="f-package" class="block border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1">
            <option value="">Semua paket</option>
            @foreach($packages as $p)
                <option value="{{ $p }}">{{ $p }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex-1 min-w-[200px]">
        <label class="text-xs text-ink/55">Cari nama / kode / alamat</label>
        <input id="f-q" class="block w-full border border-ink/10 rounded-lg text-sm px-3 py-1.5 mt-1" placeholder="ketik untuk cari...">
    </div>
    <button id="btn-apply" class="px-4 py-2 bg-accent text-ink rounded-xl text-sm font-semibold">Apply</button>
    <button id="btn-reset" class="px-3 py-2 text-sm text-ink/55 hover:bg-cream-deep/60 rounded-xl">Reset</button>
    <span id="map-count" class="text-xs text-ink/55 ml-2"></span>
</div>

<div id="map" class="bg-cream-deep/30"></div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
(function () {
    // Default view: Indonesia (Jakarta) — auto-fits to markers when data loads
    const map = L.map('map').setView([-6.200000, 106.816666], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    const cluster = L.markerClusterGroup();
    map.addLayer(cluster);

    const COLORS = {
        active:   '#10b981',
        isolir:   '#ef4444',
        inactive: '#9ca3af',
        free:     '#3b82f6',
    };

    function colorForStatus(status) {
        return COLORS[status] || '#6b7280';
    }

    function makeIcon(color) {
        return L.divIcon({
            className: 'crm-marker',
            iconSize: [22, 22],
            iconAnchor: [11, 11],
            html: `<div style="width:18px;height:18px;border-radius:50%;background:${color};border:3px solid white;box-shadow:0 1px 3px rgba(0,0,0,0.4);"></div>`
        });
    }

    function escapeHtml(s) {
        return (s == null ? '' : String(s)).replace(/[&<>"']/g, c => ({
            '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
        }[c]));
    }

    function popupHtml(c) {
        const statusBadge = `<span style="display:inline-block;padding:2px 8px;border-radius:999px;background:${colorForStatus(c.status)};color:#fff;font-size:11px;font-weight:600;">${escapeHtml(c.status)}</span>`;
        return `
            <div style="min-width:220px;">
                <div style="font-weight:700;font-size:14px;">${escapeHtml(c.name)}</div>
                <div style="font-size:11px;color:#6b7280;margin-bottom:6px;">${escapeHtml(c.code)} · ${statusBadge}</div>
                ${c.phone   ? `<div style="font-size:12px;">📱 ${escapeHtml(c.phone)}</div>` : ''}
                ${c.address ? `<div style="font-size:12px;color:#374151;margin-top:4px;">📍 ${escapeHtml(c.address)}</div>` : ''}
                ${c.package ? `<div style="font-size:11px;color:#6b7280;margin-top:6px;">${escapeHtml(c.service_type || '')} · ${escapeHtml(c.package)}</div>` : ''}
                <a href="${c.detail_url}" style="display:inline-block;margin-top:8px;font-size:12px;color:#1a1a1a;background:#f5c542;padding:4px 10px;border-radius:8px;font-weight:600;text-decoration:none;">Edit pelanggan →</a>
            </div>
        `;
    }

    async function loadCustomers() {
        const params = new URLSearchParams();
        const s = document.getElementById('f-status').value;       if (s) params.set('status', s);
        const t = document.getElementById('f-service-type').value; if (t) params.set('service_type', t);
        const p = document.getElementById('f-package').value;      if (p) params.set('package', p);
        const q = document.getElementById('f-q').value.trim();     if (q) params.set('q', q);

        const url = '{{ route('map.customers') }}' + (params.toString() ? ('?' + params.toString()) : '');
        const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
        const json = await res.json();

        cluster.clearLayers();
        const bounds = [];
        for (const c of json.data) {
            const m = L.marker([c.lat, c.lng], { icon: makeIcon(colorForStatus(c.status)) });
            m.bindPopup(popupHtml(c));
            cluster.addLayer(m);
            bounds.push([c.lat, c.lng]);
        }
        document.getElementById('map-count').textContent = `Menampilkan ${json.count} pelanggan`;
        if (bounds.length > 0) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 16 });
    }

    document.getElementById('btn-apply').addEventListener('click', loadCustomers);
    document.getElementById('btn-reset').addEventListener('click', () => {
        ['f-status','f-service-type','f-package'].forEach(id => document.getElementById(id).value = '');
        document.getElementById('f-q').value = '';
        loadCustomers();
    });
    document.getElementById('f-q').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') { e.preventDefault(); loadCustomers(); }
    });

    loadCustomers();
})();
</script>
@endsection
