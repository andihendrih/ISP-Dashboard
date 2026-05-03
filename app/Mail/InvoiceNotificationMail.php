<?php

namespace App\Mail;

use App\Models\CustomerProfile;
use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public ?CustomerProfile $customer = null,
        public ?Invoice $invoice = null,
    ) {
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('mail.invoice-notification')
            ->with([
                'subjectLine' => $this->subjectLine,
                'bodyText'    => $this->bodyText,
                'customer'    => $this->customer,
                'invoice'     => $this->invoice,
            ]);
    }
}
