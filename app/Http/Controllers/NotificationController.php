<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\NotificationLog;
use App\Models\NotificationSetting;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function settings(): View
    {
        $keys = [
            'channel.wa', 'channel.email',
            'template.invoice_created', 'template.reminder_h3',
            'template.reminder_h0', 'template.reminder_overdue',
            'template.payment_received',
        ];

        $settings = [];
        foreach ($keys as $k) {
            $settings[$k] = NotificationSetting::isEnabled($k, true);
        }

        $provider = app(\App\Services\Notifications\Contracts\WaProvider::class)->name();

        return view('notifications.settings', compact('settings', 'provider'));
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $keys = [
            'channel.wa', 'channel.email',
            'template.invoice_created', 'template.reminder_h3',
            'template.reminder_h0', 'template.reminder_overdue',
            'template.payment_received',
        ];

        foreach ($keys as $k) {
            NotificationSetting::setEnabled($k, $request->boolean(str_replace('.', '_', $k)));
        }

        return back()->with('success', 'Pengaturan notifikasi disimpan.');
    }

    public function testSend(Request $request, NotificationService $svc): RedirectResponse
    {
        $request->validate([
            'channel' => 'required|in:wa,email',
            'target'  => 'required|string|max:160',
        ]);

        $log = $request->channel === 'wa'
            ? $svc->sendTestWa($request->target)
            : $svc->sendTestEmail($request->target);

        $msg = $log->status === 'sent'
            ? "Test berhasil dikirim ke {$log->recipient}."
            : "Gagal: " . substr((string) $log->provider_response, 0, 200);

        return back()->with($log->status === 'sent' ? 'success' : 'error', $msg);
    }

    public function logs(Request $request): View
    {
        $logs = NotificationLog::query()
            ->with(['customer:id,full_name,customer_code', 'invoice:id,invoice_number'])
            ->when($request->filled('channel'), fn ($q) => $q->where('channel', $request->channel))
            ->when($request->filled('status'),  fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(50)
            ->withQueryString();

        return view('notifications.logs', compact('logs'));
    }

    public function sendInvoiceNotification(Request $request, Invoice $invoice, NotificationService $svc): RedirectResponse
    {
        $request->validate([
            'template' => 'required|in:invoice_created,reminder_h3,reminder_h0,reminder_overdue,payment_received',
            'channels' => 'required|array|min:1',
            'channels.*' => 'in:wa,email',
        ]);

        $invoice->load('customer');
        if (!$invoice->customer) {
            return back()->with('error', 'Pelanggan tidak ditemukan.');
        }

        $logs = $svc->dispatch($request->template, $invoice->customer, $invoice, $request->channels);

        if (empty($logs)) {
            return back()->with('error', 'Notifikasi tidak terkirim — cek kontak pelanggan & pengaturan.');
        }

        $sent = collect($logs)->where('status', 'sent')->count();
        $fail = count($logs) - $sent;

        return back()->with($fail === 0 ? 'success' : 'error',
            "Notifikasi: {$sent} terkirim, {$fail} gagal. Cek log untuk detail.");
    }
}
