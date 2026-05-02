<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Struk {{ $invoice->invoice_number }}</title>
<style>
    @page { margin: 8px; }
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans Mono, monospace; font-size: 9.5px; color: #000; line-height: 1.35; }

    .center { text-align: center; }
    .right  { text-align: right; }
    .b      { font-weight: bold; }
    .lg     { font-size: 11px; }
    .xl     { font-size: 13px; }
    .upper  { text-transform: uppercase; }

    hr.dashed { border: 0; border-top: 1px dashed #000; margin: 4px 0; }
    hr.solid  { border: 0; border-top: 1px solid #000; margin: 3px 0; }

    /* brand bar */
    .brand-bar { width: 100%; }
    .brand-bar .left  { float: left;  width: 60%; font-weight: bold; }
    .brand-bar .right { float: right; width: 40%; text-align: right; }
    .brand-bar:after  { content: ""; display: block; clear: both; }

    .brand-name {
        font-size: 13px;
        font-weight: 900;
        letter-spacing: 0.5px;
    }
    .brand-tag {
        font-size: 8px;
        font-weight: normal;
        margin-top: 1px;
        color: #333;
    }

    /* key-value rows: label fixed-width then ":" then value */
    table.kv { width: 100%; border-collapse: collapse; }
    table.kv td {
        padding: 0;
        vertical-align: top;
        font-size: 9.5px;
    }
    table.kv td.k { width: 38%; white-space: nowrap; text-transform: uppercase; }
    table.kv td.s { width: 4px;  white-space: nowrap; }
    table.kv td.v { text-align: right; word-break: break-all; }
    table.kv td.v.left { text-align: left; }

    .total-row td {
        font-weight: bold;
        font-size: 12px;
        padding-top: 3px;
    }

    .footer-msg {
        font-size: 9px;
        text-align: center;
        margin-top: 6px;
    }
</style>
</head>
<body>

{{-- ============== Brand bar (mirip FASTPAY logo + bank label) ============== --}}
<div class="brand-bar">
    <div class="left">
        <span class="brand-name">{{ strtoupper(config('ahnet.brand', 'AHNET')) }}</span>
        @if(config('ahnet.outlet.tagline'))
            <div class="brand-tag upper">{{ config('ahnet.outlet.tagline') }}</div>
        @endif
    </div>
    <div class="right b upper" style="font-size: 8.5px;">{{ config('ahnet.company', 'PT. AHNet') }}</div>
</div>

<div style="height: 6px;"></div>

{{-- ============== Outlet info ============== --}}
<div class="upper b">{{ config('ahnet.outlet.name', 'AHNET MULTIPAYMENT') }}</div>
<div class="upper" style="font-size: 9px;">{{ config('ahnet.outlet.address', '-') }}</div>
<div>{{ config('ahnet.outlet.phone', '-') }}</div>

<div style="height: 6px;"></div>

{{-- ============== Section title ============== --}}
<div class="upper b" style="font-size: 9px;">{{ strtoupper($invoice->customer?->service_type ?? 'INTERNET') }}</div>
<div class="upper b">STRUK PEMBAYARAN TAGIHAN INTERNET</div>

<div style="height: 6px;"></div>

{{-- ============== Detail rows (key : value) ============== --}}
<table class="kv">
    <tr>
        <td class="k">TANGGAL</td><td class="s">:</td>
        <td class="v">{{ now()->format('d-m-Y H:i:s') }}</td>
    </tr>
    <tr>
        <td class="k">NO. RESI</td><td class="s">:</td>
        <td class="v b">{{ $invoice->invoice_number }}</td>
    </tr>
    <tr>
        <td class="k">IDPEL</td><td class="s">:</td>
        <td class="v">{{ $invoice->customer?->customer_code ?? '-' }}</td>
    </tr>
    <tr>
        <td class="k">NAMA</td><td class="s">:</td>
        <td class="v upper">{{ $invoice->customer?->full_name ?? '-' }}</td>
    </tr>
    <tr>
        <td class="k">PAKET</td><td class="s">:</td>
        <td class="v upper">{{ $invoice->servicePlan?->name ?? 'INTERNET' }}@if($invoice->servicePlan?->rate_limit) / {{ $invoice->servicePlan->rate_limit }}@endif</td>
    </tr>
    <tr>
        <td class="k">BL/TH</td><td class="s">:</td>
        <td class="v upper">{{ \Carbon\Carbon::create($invoice->period_year, $invoice->period_month, 1)->format('M') }}{{ substr($invoice->period_year, -2) }}</td>
    </tr>
    <tr>
        <td class="k">PERIODE</td><td class="s">:</td>
        <td class="v">{{ $invoice->period_start->format('d/m') }} - {{ $invoice->period_end->format('d/m') }}</td>
    </tr>
    @if($invoice->is_prorated)
        <tr>
            <td class="k">HARI</td><td class="s">:</td>
            <td class="v">{{ $invoice->days_charged }}/{{ $invoice->days_in_month }} (PRORATE)</td>
        </tr>
    @endif
    <tr>
        <td class="k">JATUH TEMPO</td><td class="s">:</td>
        <td class="v">{{ optional($invoice->due_date)->format('d-m-Y') }}</td>
    </tr>
    @if($invoice->customer?->radius_username)
        <tr>
            <td class="k">USER RADIUS</td><td class="s">:</td>
            <td class="v">{{ $invoice->customer->radius_username }}</td>
        </tr>
    @endif

    <tr><td colspan="3"><hr class="dashed"></td></tr>

    <tr>
        <td class="k">RP TAG INTERNET</td><td class="s">:</td>
        <td class="v">Rp {{ number_format($invoice->base_amount, 0, ',', '.') }}</td>
    </tr>
    @if((float) $invoice->discount_amount > 0)
        <tr>
            <td class="k">DISKON</td><td class="s">:</td>
            <td class="v">- Rp {{ number_format($invoice->discount_amount, 0, ',', '.') }}</td>
        </tr>
    @endif
    @if((float) $invoice->tax_amount > 0)
        <tr>
            <td class="k">PPN</td><td class="s">:</td>
            <td class="v">Rp {{ number_format($invoice->tax_amount, 0, ',', '.') }}</td>
        </tr>
    @endif
    <tr>
        <td class="k">ADMIN</td><td class="s">:</td>
        <td class="v">Rp 0</td>
    </tr>

    <tr><td colspan="3"><hr class="solid"></td></tr>

    <tr class="total-row">
        <td class="k">TOTAL BAYAR</td><td class="s">:</td>
        <td class="v">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
    </tr>
</table>

@if($invoice->payments->isNotEmpty())
    <div style="height: 6px;"></div>
    <hr class="dashed">
    <div class="b">PEMBAYARAN</div>
    @foreach($invoice->payments->sortBy('paid_at') as $pay)
        <table class="kv">
            <tr>
                <td class="k">VIA</td><td class="s">:</td>
                <td class="v upper b">{{ $pay->method }}</td>
            </tr>
            @if($pay->reference)
                <tr>
                    <td class="k">NO REF</td><td class="s">:</td>
                    <td class="v">{{ $pay->reference }}</td>
                </tr>
            @endif
            <tr>
                <td class="k">TANGGAL</td><td class="s">:</td>
                <td class="v">{{ $pay->paid_at->format('d-m-Y H:i') }}</td>
            </tr>
            <tr>
                <td class="k">DITERIMA</td><td class="s">:</td>
                <td class="v b">Rp {{ number_format($pay->amount, 0, ',', '.') }}</td>
            </tr>
        </table>
        @if(!$loop->last)<hr class="dashed">@endif
    @endforeach

    @if((float) $invoice->paid_amount < (float) $invoice->total_amount)
        <hr class="dashed">
        <table class="kv">
            <tr>
                <td class="k b">SISA</td><td class="s">:</td>
                <td class="v b">Rp {{ number_format($invoice->outstanding, 0, ',', '.') }}</td>
            </tr>
        </table>
    @endif
@endif

<div style="height: 8px;"></div>
<hr class="dashed">

@if($invoice->status === \App\Models\Invoice::STATUS_LUNAS)
    <div class="center b xl">*** LUNAS ***</div>
@elseif($invoice->status === \App\Models\Invoice::STATUS_CANCELLED)
    <div class="center b lg">*** DIBATALKAN ***</div>
@elseif($invoice->status === \App\Models\Invoice::STATUS_TERLAMBAT)
    <div class="center b">!!! TERLAMBAT !!!</div>
@else
    <div class="center b">BELUM LUNAS</div>
@endif

<div class="footer-msg">
    {{ strtoupper(config('ahnet.brand', 'AHNet')) }} MENYATAKAN STRUK INI SBG BUKTI<br>
    PEMBAYARAN YG SAH, MHN DISIMPAN
</div>

<div style="height: 6px;"></div>

<div class="center b">TERIMA KASIH</div>
<div class="center">INFORMASI HUB : {{ config('ahnet.outlet.cs', '-') }}</div>

</body>
</html>
