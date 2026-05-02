<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoice->invoice_number }}</title>
<style>
    @page { margin: 28px 32px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; }
    h1, h2, h3 { margin: 0; }
    .row { width: 100%; }
    .row:after { content: ""; display: table; clear: both; }
    .col-half { width: 50%; float: left; }
    .col-1-3 { width: 33.333%; float: left; }
    .col-2-3 { width: 66.666%; float: left; }
    .right { text-align: right; }
    .center { text-align: center; }
    .mono { font-family: DejaVu Sans Mono, monospace; }
    .muted { color: #6b6b6b; }
    .small { font-size: 10px; }

    .header {
        background: #1a1a1a;
        color: #fff;
        padding: 18px 24px;
        border-radius: 8px;
        margin-bottom: 18px;
    }
    .header .brand {
        font-size: 18px;
        font-weight: bold;
        letter-spacing: 0.5px;
    }
    .header .brand-sub {
        font-size: 10px;
        color: #d0d0d0;
        margin-top: 2px;
    }
    .header .accent {
        color: #f5c542;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .b-belum_lunas { background: #fff3c4; color: #6b4e00; }
    .b-lunas       { background: #d1f0d4; color: #1f6f2c; }
    .b-terlambat   { background: #ffd6d6; color: #7a1a1a; }
    .b-cancelled   { background: #e0e0e0; color: #555; }

    .card {
        border: 1px solid #e8e1cf;
        background: #faf3e0;
        padding: 12px 14px;
        border-radius: 6px;
        margin-bottom: 10px;
    }
    .card h3 {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6b6b6b;
        margin-bottom: 6px;
        font-weight: bold;
    }
    .label { color: #6b6b6b; }

    table.lines {
        width: 100%;
        border-collapse: collapse;
        margin-top: 14px;
    }
    table.lines th, table.lines td {
        padding: 9px 10px;
        text-align: left;
        border-bottom: 1px solid #e8e1cf;
    }
    table.lines thead th {
        background: #1a1a1a;
        color: #fff;
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    table.lines tbody tr.totals td {
        border-bottom: none;
        padding: 5px 10px;
    }
    table.lines tbody tr.totals.grand td {
        background: #1a1a1a;
        color: #f5c542;
        font-weight: bold;
        font-size: 13px;
        padding: 11px 10px;
        border-radius: 4px;
    }

    .footer {
        margin-top: 28px;
        font-size: 9px;
        color: #999;
        border-top: 1px solid #e8e1cf;
        padding-top: 8px;
    }
    .stamp {
        position: absolute;
        bottom: 90px;
        right: 36px;
        transform: rotate(-12deg);
        border: 4px solid #1f6f2c;
        color: #1f6f2c;
        padding: 8px 24px;
        font-weight: bold;
        font-size: 28px;
        letter-spacing: 4px;
        opacity: 0.85;
        border-radius: 6px;
    }
    .stamp.cancel {
        border-color: #7a1a1a;
        color: #7a1a1a;
    }
</style>
</head>
<body>

<div class="header">
    <div class="row">
        <div class="col-2-3">
            <div class="brand">{{ config('ahnet.brand', 'AHNet') }}</div>
            <div class="brand-sub">{{ config('ahnet.company', 'PT. AHNet') }}</div>
        </div>
        <div class="col-1-3 right">
            <div style="font-size: 22px; font-weight: bold;" class="accent">INVOICE</div>
            <div class="mono small" style="margin-top: 2px;">{{ $invoice->invoice_number }}</div>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 12px;">
    <div class="col-half">
        <div class="card">
            <h3>Tagihan untuk</h3>
            <div style="font-size: 13px; font-weight: bold;">{{ $invoice->customer?->full_name ?? '-' }}</div>
            <div class="mono small muted">{{ $invoice->customer?->customer_code }}</div>
            @if($invoice->customer?->phone)<div class="small">Telp: {{ $invoice->customer->phone }}</div>@endif
            @if($invoice->customer?->email)<div class="small">{{ $invoice->customer->email }}</div>@endif
            @if($invoice->customer?->address)<div class="small" style="margin-top: 4px;">{{ $invoice->customer->address }}</div>@endif
        </div>
    </div>
    <div class="col-half">
        <div class="card">
            <h3>Detail Invoice</h3>
            <table style="width:100%; font-size: 11px;">
                <tr><td class="label" width="40%">No. Invoice</td><td class="mono">{{ $invoice->invoice_number }}</td></tr>
                <tr><td class="label">Tanggal</td><td>{{ $invoice->created_at->format('d M Y') }}</td></tr>
                <tr><td class="label">Periode</td><td>{{ \Carbon\Carbon::create($invoice->period_year, $invoice->period_month, 1)->format('F Y') }}</td></tr>
                <tr><td class="label">Jatuh Tempo</td><td><strong>{{ optional($invoice->due_date)->format('d M Y') }}</strong></td></tr>
                <tr><td class="label">Status</td><td><span class="badge b-{{ $invoice->status }}">{{ $invoice->status_label }}</span></td></tr>
            </table>
        </div>
    </div>
</div>

<table class="lines">
    <thead>
        <tr>
            <th>Deskripsi</th>
            <th class="center" width="100">Periode</th>
            <th class="right" width="90">Hari</th>
            <th class="right" width="120">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>{{ $invoice->servicePlan?->name ?? 'Layanan Internet' }}</strong>
                @if($invoice->servicePlan?->rate_limit) — {{ $invoice->servicePlan->rate_limit }}@endif
                @if($invoice->is_prorated)
                    <div class="small muted">Prorate dari {{ $invoice->period_start->format('d M') }} sampai {{ $invoice->period_end->format('d M Y') }}</div>
                @endif
            </td>
            <td class="center small">{{ $invoice->period_start->format('d/m') }} – {{ $invoice->period_end->format('d/m') }}</td>
            <td class="right">{{ $invoice->days_charged }} / {{ $invoice->days_in_month }}</td>
            <td class="right mono">Rp {{ number_format($invoice->base_amount, 0, ',', '.') }}</td>
        </tr>

        <tr class="totals"><td colspan="3" class="right label">Subtotal</td><td class="right mono">Rp {{ number_format($invoice->base_amount, 0, ',', '.') }}</td></tr>
        @if((float)$invoice->discount_amount > 0)
            <tr class="totals"><td colspan="3" class="right label">Diskon</td><td class="right mono">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td></tr>
        @endif
        @if((float)$invoice->tax_amount > 0)
            <tr class="totals"><td colspan="3" class="right label">PPN</td><td class="right mono">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td></tr>
        @endif
        <tr class="totals grand"><td colspan="3" class="right">TOTAL TAGIHAN</td><td class="right mono">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td></tr>

        @if((float)$invoice->paid_amount > 0)
            <tr class="totals"><td colspan="3" class="right label" style="padding-top: 8px;">Sudah Dibayar</td><td class="right mono" style="color:#1f6f2c;">- Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td></tr>
            <tr class="totals"><td colspan="3" class="right label"><strong>Sisa</strong></td><td class="right mono"><strong>Rp {{ number_format($invoice->outstanding, 0, ',', '.') }}</strong></td></tr>
        @endif
    </tbody>
</table>

@if($invoice->payments->isNotEmpty())
    <div class="card" style="margin-top: 14px;">
        <h3>Riwayat Pembayaran</h3>
        <table style="width:100%; font-size: 10px;">
            <thead>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th class="left" style="padding: 4px 0;">Tanggal</th>
                    <th class="left">Metode</th>
                    <th class="left">Referensi</th>
                    <th class="right">Jumlah</th>
                </tr>
            </thead>
            <tbody>
            @foreach($invoice->payments->sortBy('paid_at') as $pay)
                <tr>
                    <td style="padding: 3px 0;">{{ $pay->paid_at->format('d M Y H:i') }}</td>
                    <td style="text-transform: uppercase;">{{ $pay->method }}</td>
                    <td class="mono small">{{ $pay->reference ?? '-' }}</td>
                    <td class="right mono">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif

@if($invoice->notes)
    <div class="card" style="margin-top: 10px;">
        <h3>Catatan</h3>
        <div class="small">{{ $invoice->notes }}</div>
    </div>
@endif

<div class="row" style="margin-top: 22px;">
    <div class="col-2-3 small muted">
        <strong>Cara Pembayaran:</strong><br>
        • Transfer ke rekening yang tertera di portal pelanggan<br>
        • QRIS / Virtual Account (lihat aplikasi)<br>
        • Bayar tunai di kantor {{ config('ahnet.company', 'AHNet') }}
    </div>
    <div class="col-1-3 right">
        <div class="small muted">Hormat kami,</div>
        <div style="margin-top: 32px; border-top: 1px solid #1a1a1a; padding-top: 4px; display: inline-block; min-width: 140px;">
            <strong>{{ config('ahnet.company', 'AHNet') }}</strong>
        </div>
    </div>
</div>

@if($invoice->status === \App\Models\Invoice::STATUS_LUNAS)
    <div class="stamp">LUNAS</div>
@elseif($invoice->status === \App\Models\Invoice::STATUS_CANCELLED)
    <div class="stamp cancel">DIBATALKAN</div>
@endif

<div class="footer">
    Invoice digenerate otomatis oleh sistem {{ config('ahnet.brand', 'AHNet') }} pada {{ now()->format('d M Y H:i') }} WIB.
    Dokumen ini sah tanpa tanda tangan basah.
</div>

</body>
</html>
