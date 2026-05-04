<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>{{ $subjectLine }}</title>
</head>
<body style="margin:0; padding:0; background:#f4ecd8; font-family: Arial, sans-serif; color:#1a1a1a;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr><td align="center" style="padding:24px 12px;">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; background:#ffffff; border-radius:16px; border:1px solid #e6dcc1; overflow:hidden;">
            <tr><td style="padding:20px 28px; background:#1a1a1a; color:#f4ecd8;">
                <strong style="font-size:18px;">{{ config('ahnet.brand_name', 'AHNet') }}</strong>
                <span style="font-size:11px; color:#f5c542; margin-left:8px;">ISP DASHBOARD</span>
            </td></tr>
            <tr><td style="padding:28px 28px 12px 28px;">
                <h2 style="margin:0 0 12px 0; font-size:18px;">{{ $subjectLine }}</h2>
                <pre style="margin:0; padding:0; font-family:Arial,sans-serif; font-size:14px; line-height:1.6; white-space:pre-wrap; color:#1a1a1a;">{{ $bodyText }}</pre>
            </td></tr>

            @if($invoice)
            <tr><td style="padding:8px 28px 24px 28px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fdf6e3; border:1px solid #f3e6c0; border-radius:12px;">
                    <tr><td style="padding:14px 18px;">
                        <div style="font-size:11px; color:#8a7e5b; text-transform:uppercase; letter-spacing:.05em;">Detail Invoice</div>
                        <div style="font-size:14px; margin-top:6px;">
                            <strong>{{ $invoice->invoice_number }}</strong><br>
                            Periode: {{ sprintf('%04d-%02d', $invoice->period_year, $invoice->period_month) }}<br>
                            Total: <strong>Rp {{ number_format((float) $invoice->total_amount, 0, ',', '.') }}</strong><br>
                            Jatuh tempo: {{ optional($invoice->due_date)->format('d M Y') }}
                        </div>
                    </td></tr>
                </table>
            </td></tr>
            @endif

            <tr><td style="padding:18px 28px 24px 28px; border-top:1px solid #f0e7ce; font-size:12px; color:#6b6555;">
                Email otomatis dari ISP Dashboard {{ config('ahnet.brand_name', 'AHNet') }}.<br>
                Hubungi CS: {{ config('ahnet.outlet.cs_phone', config('ahnet.outlet.phone', '-')) }}
            </td></tr>
        </table>
    </td></tr>
</table>
</body>
</html>
