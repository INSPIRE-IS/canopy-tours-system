<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    use Queueable, SerializesModels;

    protected array $invoiceData;
    protected $pdf;

    public function __construct(array $invoiceData, $pdf)
    {
        $this->invoiceData = $invoiceData;
        $this->pdf = $pdf;
    }

    public function build()
    {
        return $this->subject('Your Invoice #' . ($this->invoiceData['invoice']['number'] ?? ''))
            ->view('emails.invoice', $this->invoiceData)
            ->attachData(
                $this->pdf->output(),
                "invoice-{$this->invoiceData['invoice']['number']}.pdf",
                ['mime' => 'application/pdf']
            );
    }
}
