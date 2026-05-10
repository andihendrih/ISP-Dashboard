<?php

namespace App\Http\Controllers;

use App\Models\CustomerProfile;
use App\Models\SupportTicket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $q = SupportTicket::query()
            ->with(['customer', 'assignee'])
            ->orderByRaw("CASE status
                WHEN 'open' THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'pending_customer' THEN 3
                WHEN 'resolved' THEN 4
                WHEN 'closed' THEN 5
                ELSE 6 END")
            ->orderByDesc('created_at');

        if ($s = $request->query('q')) {
            $q->where(function ($w) use ($s) {
                $w->where('ticket_number', 'like', "%{$s}%")
                  ->orWhere('subject', 'like', "%{$s}%")
                  ->orWhereHas('customer', function ($c) use ($s) {
                      $c->where('full_name', 'like', "%{$s}%")
                        ->orWhere('customer_code', 'like', "%{$s}%");
                  });
            });
        }
        if ($status = $request->query('status'))     $q->where('status', $status);
        if ($cat    = $request->query('category'))   $q->where('category', $cat);
        if ($prio   = $request->query('priority'))   $q->where('priority', $prio);

        $stats = [
            'open'        => SupportTicket::where('status', 'open')->count(),
            'in_progress' => SupportTicket::where('status', 'in_progress')->count(),
            'pending'     => SupportTicket::where('status', 'pending_customer')->count(),
            'resolved'    => SupportTicket::where('status', 'resolved')->count(),
        ];

        return view('tickets.index', [
            'tickets' => $q->paginate(20)->withQueryString(),
            'stats'   => $stats,
        ]);
    }

    public function create(): View
    {
        return view('tickets.create', [
            'customers' => CustomerProfile::orderBy('full_name')->get(['id', 'full_name', 'customer_code', 'phone']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_profile_id' => ['nullable', 'integer', 'exists:customer_profiles,id'],
            'subject'             => ['required', 'string', 'max:200'],
            'category'            => ['required', 'in:gangguan,billing,instalasi,pindah_alamat,upgrade,lainnya'],
            'priority'            => ['required', 'in:low,normal,high,urgent'],
            'description'         => ['required', 'string', 'max:5000'],
            'contact_phone'       => ['nullable', 'string', 'max:32'],
            'assigned_to'         => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $data['ticket_number'] = $this->nextTicketNumber();
        $data['created_by']    = Auth::id();
        $data['status']        = SupportTicket::STATUS_OPEN;

        if (empty($data['contact_phone']) && !empty($data['customer_profile_id'])) {
            $cust = CustomerProfile::find($data['customer_profile_id']);
            $data['contact_phone'] = $cust?->phone;
        }

        $tk = SupportTicket::create($data);

        return redirect()->route('tickets.show', $tk)->with('success', "Tiket {$tk->ticket_number} berhasil dibuat.");
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['customer.servicePlan', 'assignee', 'creator', 'comments.user']);
        return view('tickets.show', [
            'ticket' => $ticket,
            'staff'  => User::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function comment(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'body'        => ['required', 'string', 'max:5000'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        TicketComment::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => Auth::id(),
            'body'        => $data['body'],
            'is_internal' => (bool) ($data['is_internal'] ?? false),
        ]);

        // Mark first response timestamp + auto-promote open → in_progress
        if (!$ticket->first_response_at) {
            $ticket->first_response_at = now();
        }
        if ($ticket->status === SupportTicket::STATUS_OPEN) {
            $ticket->status = SupportTicket::STATUS_IN_PROGRESS;
        }
        $ticket->save();

        return back()->with('success', 'Balasan terkirim.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status'     => ['required', 'in:open,in_progress,pending_customer,resolved,closed'],
            'resolution' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->status = $data['status'];

        if ($data['status'] === SupportTicket::STATUS_RESOLVED) {
            $ticket->resolved_at = now();
            $ticket->resolution  = $data['resolution'] ?? $ticket->resolution;
        } elseif ($data['status'] === SupportTicket::STATUS_CLOSED) {
            $ticket->closed_at = now();
            if (!$ticket->resolved_at) $ticket->resolved_at = now();
        } elseif (in_array($data['status'], [SupportTicket::STATUS_OPEN, SupportTicket::STATUS_IN_PROGRESS])) {
            // re-opening
            $ticket->resolved_at = null;
            $ticket->closed_at   = null;
        }

        $ticket->save();

        return back()->with('success', "Status tiket diubah ke '{$ticket->status_label}'.");
    }

    public function assign(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $ticket->assigned_to = $data['assigned_to'] ?: null;
        if ($ticket->status === SupportTicket::STATUS_OPEN && $ticket->assigned_to) {
            $ticket->status = SupportTicket::STATUS_IN_PROGRESS;
        }
        $ticket->save();

        return back()->with('success', $ticket->assigned_to ? "Tiket di-assign ke {$ticket->assignee?->name}." : 'Assignment dilepas.');
    }

    protected function nextTicketNumber(): string
    {
        $prefix = 'TKT-' . now()->format('Ym') . '-';
        $last = SupportTicket::where('ticket_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('ticket_number');
        $n = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $n = ((int) $m[1]) + 1;
        }
        do {
            $candidate = $prefix.str_pad((string) $n, 4, '0', STR_PAD_LEFT);
            $n++;
        } while (SupportTicket::where('ticket_number', $candidate)->exists());

        return $candidate;
    }
}
