<?php

namespace App\Services\Notifications;

use App\Mail\InvoiceNotificationMail;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Services\Notifications\Contracts\WaProvider;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(private WaProvider $wa)
    {
    }

    public function provider(): WaProvider
    {
        return $this->wa;
    }

    /**
     * Dispatch notifikasi by template ke pelanggan.
     * Channels: ['wa'], ['email'], ['wa','email']
     *
     * @return array<int, NotificationLog>
     */
    public function dispatch(
        string $template,
        CustomerProfile $customer,
        ?Invoice $invoice = null,
        array $channels = ['wa', 'email'],
    ): array {
        if (!NotificationSetting::isEnabled("template.$template", true)) {
            return [];
        }

        $payload = $this->renderTemplate($template, $customer, $invoice);
        $logs    = [];

        foreach ($channels as $ch) {
            if ($ch === 'wa') {
                if (!NotificationSetting::isEnabled('channel.wa', true)) continue;
                if (empty($customer->phone)) continue;
                $logs[] = $this->sendWa($template, $customer, $invoice, $payload['wa']);
            } elseif ($ch === 'email') {
                if (!NotificationSetting::isEnabled('channel.email', true)) continue;
                if (empty($customer->email)) continue;
                $logs[] = $this->sendEmail($template, $customer, $invoice, $payload['email']);
            }
        }

        return $logs;
    }

    public function sendTestWa(string $phone, string $body = 'Test koneksi WA AHNet 🚀'): NotificationLog
    {
        $log = NotificationLog::create([
            'channel'   => 'wa',
            'template'  => 'test',
            'recipient' => $phone,
            'body'      => $body,
            'status'    => 'pending',
        ]);

        $result = $this->wa->send($phone, $body);
        $log->update([
            'status'            => $result['ok'] ? 'sent' : 'failed',
            'provider_response' => $result['message'],
            'sent_at'           => $result['ok'] ? now() : null,
        ]);

        return $log;
    }

    public function sendTestEmail(string $email, string $subject = 'Test Email AHNet', string $body = 'Halo, ini test email dari ISP Dashboard AHNet.'): NotificationLog
    {
        $log = NotificationLog::create([
            'channel'   => 'email',
            'template'  => 'test',
            'recipient' => $email,
            'subject'   => $subject,
            'body'      => $body,
            'status'    => 'pending',
        ]);

        try {
            Mail::to($email)->send(new InvoiceNotificationMail($subject, $body));
            $log->update(['status' => 'sent', 'sent_at' => now(), 'provider_response' => 'OK']);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'provider_response' => $e->getMessage()]);
            Log::warning('Email test failed: ' . $e->getMessage());
        }

        return $log;
    }

    private function sendWa(string $template, CustomerProfile $customer, ?Invoice $invoice, string $body): NotificationLog
    {
        $log = NotificationLog::create([
            'channel'             => 'wa',
            'template'            => $template,
            'recipient'           => $customer->phone,
            'body'                => $body,
            'status'              => 'pending',
            'customer_profile_id' => $customer->id,
            'invoice_id'          => $invoice?->id,
        ]);

        $result = $this->wa->send($customer->phone, $body);
        $log->update([
            'status'            => $result['ok'] ? 'sent' : 'failed',
            'provider_response' => substr((string) $result['message'], 0, 1000),
            'sent_at'           => $result['ok'] ? now() : null,
        ]);

        return $log;
    }

    private function sendEmail(string $template, CustomerProfile $customer, ?Invoice $invoice, array $payload): NotificationLog
    {
        $log = NotificationLog::create([
            'channel'             => 'email',
            'template'            => $template,
            'recipient'           => $customer->email,
            'subject'             => $payload['subject'],
            'body'                => $payload['body'],
            'status'              => 'pending',
            'customer_profile_id' => $customer->id,
            'invoice_id'          => $invoice?->id,
        ]);

        try {
            Mail::to($customer->email)->send(new InvoiceNotificationMail(
                $payload['subject'],
                $payload['body'],
                $customer,
                $invoice,
            ));
            $log->update(['status' => 'sent', 'sent_at' => now(), 'provider_response' => 'OK']);
        } catch (\Throwable $e) {
            Log::warning("Email '$template' to {$customer->email} failed: " . $e->getMessage());
            $log->update(['status' => 'failed', 'provider_response' => $e->getMessage()]);
        }

        return $log;
    }

    /**
     * Render body untuk semua channel.
     * Return ['wa' => 'plain text', 'email' => ['subject' => ..., 'body' => 'plain text yang dipakai di mailable']]
     */
    private function renderTemplate(string $template, CustomerProfile $customer, ?Invoice $invoice): array
    {
        $brand = config('ahnet.brand_name', config('app.name', 'AHNet'));
        $cs    = config('ahnet.outlet.cs_phone', config('ahnet.outlet.phone', '-'));
        $name  = $customer->full_name;

        $invLine = '';
        $subjectInv = '';
        if ($invoice) {
            $total = 'Rp ' . number_format((float) $invoice->total_amount, 0, ',', '.');
            $due   = optional($invoice->due_date)->format('d M Y');
            $period = sprintf('%04d-%02d', $invoice->period_year, $invoice->period_month);
            $invLine = "Invoice: {$invoice->invoice_number}\nPeriode: {$period}\nTotal: {$total}\nJatuh tempo: {$due}";
            $subjectInv = " - {$invoice->invoice_number}";
        }

        $body = match ($template) {
            'invoice_created' => "Halo {$name},\n\nTagihan internet baru lo udah terbit.\n\n{$invLine}\n\nLogin / cek invoice di dashboard AHNet ya. Kalo udah bayar, tinggal forward bukti transfer ke admin.\n\nTerima kasih,\n{$brand}\nCS: {$cs}",
            'reminder_h3'     => "Halo {$name},\n\nIni reminder tagihan internet AHNet bulan ini, **3 hari lagi jatuh tempo**.\n\n{$invLine}\n\nMohon dibayar tepat waktu ya biar layanan ga ke-isolir.\n\nTerima kasih,\n{$brand}\nCS: {$cs}",
            'reminder_h0'     => "Halo {$name},\n\nHari ini **jatuh tempo** tagihan internet AHNet bulan ini:\n\n{$invLine}\n\nMohon dibayar hari ini ya. Kalo udah, abaikan pesan ini.\n\nTerima kasih,\n{$brand}\nCS: {$cs}",
            'reminder_overdue' => "Halo {$name},\n\nTagihan internet AHNet udah **lewat jatuh tempo**:\n\n{$invLine}\n\nLayanan akan diisolir kalau gak dibayar dalam 3 hari ke depan. Hubungi CS kalo butuh keringanan.\n\n{$brand}\nCS: {$cs}",
            'payment_received' => "Halo {$name},\n\nPembayaran lo udah kami terima ✅\n\n{$invLine}\nStatus: LUNAS\n\nTerima kasih atas pembayarannya.\n{$brand}",
            default => "Halo {$name},\n\nNotifikasi dari {$brand}.\n\n{$invLine}",
        };

        $subject = match ($template) {
            'invoice_created'  => "Tagihan Internet Baru{$subjectInv}",
            'reminder_h3'      => "Reminder: 3 hari lagi jatuh tempo{$subjectInv}",
            'reminder_h0'      => "Reminder: Hari ini jatuh tempo{$subjectInv}",
            'reminder_overdue' => "PENTING: Tagihan lewat jatuh tempo{$subjectInv}",
            'payment_received' => "Pembayaran diterima{$subjectInv}",
            default            => "Notifikasi {$brand}",
        };

        return [
            'wa'    => $body,
            'email' => ['subject' => $subject, 'body' => $body],
        ];
    }
}
