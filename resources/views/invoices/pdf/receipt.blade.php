<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Struk {{ $invoice->invoice_number }}</title>
<style>
    @page { margin: 8px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans Mono, monospace; font-size: 9.5px; color: #000; }
    .center { text-align: center; }
    .right  { text-align: right; }
    .b { font-weight: bold; }
    .lg { font-size: 11px; }
    hr { border: 0; border-top: 1px dashed #000; margin: 4px 0; }
    table { width: 100%; border-collapse: collapse; }
    td { padding: 1px 0; vertical-align: top; }
    .lbl { color: #555; }
</style>
</head>
<body>

<div class="center">
    <div class="lg b">{{ strtoupper(config('ahnet.brand', 'AHNet')) }}</div>
    <div>{{ config('ahnet.company', 'PT. AHNet') }}</div>
    <div>------------ STRUK ------------</div>
</div>

<table>
    <tr><td class="lbl">No.</td><td class="right b">{{ $invoice->invoice_number }}</td></tr>
    <tr><td class="lbl">Tgl</td><td class="right">{{ now()->format('d/m/Y H:i') }}</td></tr>
    <tr><td class="lbl">Periode</td><td class="right">{{ \Carbon\Carbon::create($invoice->period_year, $invoice->period_month, 1)->format('M Y') }}</td></tr>
</table>

<hr>

<div class="b">{{ $invoice->customer?->full_name ?? '-' }}</div>
<div class="lbl">{{ $invoice->customer?->customer_code }}</div>
@if($invoice->customer?->phone)
    <div class="lbl">{{ $invoice->customer->phone }}</div>
@endif

<hr>

<table>
    <tr>
        <td>{{ $invoice->servicePlan?->name ?? 'Internet' }}</td>
        <td class="right">Rp {{ number_format($invoice->base_amount, 0, ',', '.') }}</td>
    </tr>
    @if($invoice->is_prorated)
        <tr><td colspan="2" class="lbl" style="font-size: 8.5px;">  Prorate {{ $invoice->days_charged }}/{{ $invoice->days_in_month }} hari</td></tr>
    @endif
    @if((float)$invoice->discount_amount > 0)
        <tr><td>Diskon</td><td class="right">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td></tr>
    @endif
    @if((float)$invoice->tax_amount > 0)
        <tr><td>PPN</td><td class="right">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td></tr>
    @endif
</table>

<hr>

<table>
    <tr>
        <td class="b lg">TOTAL</td>
        <td class="right b lg">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
    </tr>
    @if((float)$invoice->paid_amount > 0)
        <tr><td class="lbl">Bayar</td><td class="right">Rp {{ number_format($invoice->paid_amount, 0, ',', '.') }}</td></tr>
        <tr><td class="b">Sisa</td><td class="right b">Rp {{ number_format($invoice->outstanding, 0, ',', '.') }}</td></tr>
    @endif
</table>

@if($invoice->payments->isNotEmpty())
    <hr>
    <div class="b">PEMBAYARAN</div>
    @foreach($invoice->payments->sortBy('paid_at') as $pay)
        <table>
            <tr>
                <td>{{ $pay->paid_at->format('d/m/Y H:i') }}</td>
                <td class="right">{{ strtoupper($pay->method) }}</td>
            </tr>
            @if($pay->reference)
                <tr><td colspan="2" class="lbl" style="font-size: 8.5px;">Ref: {{ $pay->reference }}</td></tr>
            @endif
            <tr>
                <td class="lbl">Diterima</td>
                <td class="right">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
            </tr>
        </table>
    @endforeach
@endif

<hr>

@if($invoice->status === \App\Models\Invoice::STATUS_LUNAS)
    <div class="center b lg">*** LUNAS ***</div>
@elseif($invoice->status === \App\Models\Invoice::STATUS_CANCELLED)
    <div class="center b lg">*** DIBATALKAN ***</div>
@elseif($invoice->status === \App\Models\Invoice::STATUS_TERLAMBAT)
    <div class="center b">!!! TERLAMBAT !!!</div>
    <div class="center" style="font-size: 8.5px;">Mohon segera lunasi.</div>
@else
    <div class="center b">BELUM LUNAS</div>
    <div class="center" style="font-size: 8.5px;">Jatuh tempo: {{ optional($invoice->due_date)->format('d/m/Y') }}</div>
@endif

<hr>

<div class="center" style="font-size: 8.5px;">
    Terima kasih telah berlangganan<br>
    {{ config('ahnet.brand', 'AHNet') }}
</div>

</body>
</html>
