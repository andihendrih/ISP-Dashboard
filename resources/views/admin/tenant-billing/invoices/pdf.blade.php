<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $invoice->invoice_number }}</title>
<style>
    @page { margin: 28px 32px; }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1f1d1a; margin: 0; }
    h1 { font-size: 22px; margin: 0 0 4px; letter-spacing: 0.5px; }
    h2 { font-size: 12px; margin: 12px 0 6px; color: #6b6358; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #e8e4dc; padding-bottom: 3px; }
    .muted { color: #87807a; font-size: 10px; }
    .row { width: 100%; }
    .row td { vertical-align: top; padding: 0; }
    .right { text-align: right; }
    .badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: bold; }
    .badge-paid { background: #d1fae5; color: #047857; }
    .badge-unpaid { background: #fef3c7; color: #92400e; }
    .badge-overdue { background: #fee2e2; color: #b91c1c; }
    .badge-cancelled { background: #e5e7eb; color: #4b5563; }
    table.items { width: 100%; border-collapse: collapse; margin-top: 6px; }
    table.items th, table.items td { border-bottom: 1px solid #e8e4dc; padding: 8px 6px; }
    table.items th { background: #f7f3eb; text-align: left; font-size: 10px; color: #6b6358; text-transform: uppercase; letter-spacing: 0.5px; }
    table.items td.num { text-align: right; font-family: 'DejaVu Sans Mono', monospace; }
    .total-box { background: #fcf8ef; border-radius: 6px; padding: 10px 14px; margin-top: 10px; }
    .total-row { display: table; width: 100%; }
    .total-cell { display: table-cell; vertical-align: middle; }
    .total-cell.right { text-align: right; }
    .big { font-size: 18px; font-weight: bold; color: #c47b00; }
    .footer { margin-top: 24px; font-size: 9px; color: #87807a; border-top: 1px dashed #d6d0c4; padding-top: 8px; }
</style>
</head>
<body>

<table class="row">
    <tr>
        <td>
            <h1>{{ $platform['name'] }}</h1>
            <div class="muted">{{ $platform['address'] }}</div>
            @if(!empty($platform['phone']))<div class="muted">Telp: {{ $platform['phone'] }}</div>@endif
            @if(!empty($platform['email']))<div class="muted">{{ $platform['email'] }}</div>@endif
        </td>
        <td class="right" style="width:40%">
            <div style="font-size:11px; color:#6b6358; text-transform:uppercase; letter-spacing:1px;">INVOICE LANGGANAN</div>
            <div style="font-size:16px; font-weight:bold; font-family:'DejaVu Sans Mono',monospace;">{{ $invoice->invoice_number }}</div>
            <div style="margin-top:6px">
                @php
                    $cls = match($invoice->status) {
                        'paid' => 'badge-paid',
                        'overdue' => 'badge-overdue',
                        'cancelled' => 'badge-cancelled',
                        default => 'badge-unpaid',
                    };
                @endphp
                <span class="badge {{ $cls }}">{{ strtoupper($invoice->status) }}</span>
            </div>
        </td>
    </tr>
</table>

<h2>Ditagih ke</h2>
<table class="row">
    <tr>
        <td style="width:50%">
            <div style="font-size:13px; font-weight:bold;">{{ $invoice->tenant?->name }}</div>
            <div class="muted">Code: <span style="font-family:'DejaVu Sans Mono',monospace">{{ $invoice->tenant?->code }}</span></div>
            @if($invoice->tenant?->contact_name)<div class="muted">PIC: {{ $invoice->tenant->contact_name }}</div>@endif
            @if($invoice->tenant?->contact_phone)<div class="muted">Telp: {{ $invoice->tenant->contact_phone }}</div>@endif
            @if($invoice->tenant?->contact_email)<div class="muted">Email: {{ $invoice->tenant->contact_email }}</div>@endif
        </td>
        <td style="width:50%">
            <table style="width:100%; font-size:10px;">
                <tr><td class="muted">Tanggal terbit</td><td>{{ $invoice->created_at?->format('d M Y') }}</td></tr>
                <tr><td class="muted">Jatuh tempo</td><td>{{ $invoice->due_date?->format('d M Y') }}</td></tr>
                <tr><td class="muted">Periode</td><td>{{ $invoice->period_start?->format('d M Y') }} — {{ $invoice->period_end?->format('d M Y') }}</td></tr>
                @if($invoice->paid_at)<tr><td class="muted">Tanggal lunas</td><td>{{ $invoice->paid_at?->format('d M Y H:i') }}</td></tr>@endif
            </table>
        </td>
    </tr>
</table>

<h2>Detail Tagihan</h2>
<table class="items">
    <thead>
        <tr>
            <th>Deskripsi</th>
            <th class="right" style="width:18%">Periode</th>
            <th class="right" style="width:14%">Prorate</th>
            <th class="right" style="width:18%">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <strong>Subscription Plan: {{ $invoice->plan?->name ?? '—' }}</strong>
                @if($invoice->plan?->max_customers)
                    <div class="muted">Max {{ number_format($invoice->plan->max_customers, 0, ',', '.') }} pelanggan</div>
                @endif
                @if($invoice->plan?->max_devices)
                    <div class="muted">Max {{ number_format($invoice->plan->max_devices, 0, ',', '.') }} perangkat</div>
                @endif
            </td>
            <td class="num">{{ $invoice->period_start?->format('d M') }} — {{ $invoice->period_end?->format('d M Y') }}</td>
            <td class="num">{{ number_format(((float)$invoice->prorate_factor) * 100, 2) }}%</td>
            <td class="num">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<div class="total-box">
    <div class="total-row">
        <div class="total-cell">Total Tagihan</div>
        <div class="total-cell right big">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</div>
    </div>
    @if($invoice->payments->where('status','confirmed')->count() > 0)
        <div class="total-row" style="margin-top:4px">
            <div class="total-cell muted">Sudah dibayar</div>
            <div class="total-cell right" style="color:#047857; font-weight:bold;">— Rp {{ number_format($invoice->payments->where('status','confirmed')->sum('amount'), 0, ',', '.') }}</div>
        </div>
        <div class="total-row" style="margin-top:4px; border-top:1px dashed #d6d0c4; padding-top:4px;">
            <div class="total-cell"><b>Sisa</b></div>
            <div class="total-cell right" style="font-weight:bold;">Rp {{ number_format($invoice->remainingAmount(), 0, ',', '.') }}</div>
        </div>
    @endif
</div>

@if($invoice->payments->isNotEmpty())
<h2>Riwayat Pembayaran</h2>
<table class="items">
    <thead>
        <tr>
            <th style="width:18%">Tanggal</th>
            <th style="width:18%">Metode</th>
            <th>Referensi</th>
            <th style="width:14%">Status</th>
            <th class="right" style="width:18%">Jumlah</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->payments as $pay)
        <tr>
            <td>{{ $pay->transferred_at?->format('d M Y') ?? '—' }}</td>
            <td>{{ $pay->method }}</td>
            <td class="muted">{{ $pay->reference ?? '—' }}</td>
            <td>{{ $pay->status }}</td>
            <td class="num">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if(!empty($platform['payment_info']))
<h2>Info Pembayaran</h2>
<div style="font-size:10px; line-height:1.6;">{!! nl2br(e($platform['payment_info'])) !!}</div>
@endif

<div class="footer">
    Invoice ini di-generate otomatis oleh sistem. Untuk pertanyaan, hubungi {{ $platform['email'] ?? $platform['phone'] ?? 'tim platform' }}.
</div>

</body>
</html>
