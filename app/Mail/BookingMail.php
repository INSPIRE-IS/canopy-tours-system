<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BookingMail extends Mailable
{
    use Queueable, SerializesModels;

    protected array $invoiceData;
    protected array $extraAttachments;

    public function __construct(array $invoiceData, array $extraAttachments = [])
    {
        $this->invoiceData = $invoiceData;
        $this->extraAttachments = $extraAttachments;
    }

    public function build()
    {
        $document = $this->invoiceData['document'] ?? [];
        $documentLabel = $document['label'] ?? 'Invoice';
        $documentNumber = $this->invoiceData['invoice']['number'] ?? '';
        $subject = $document['email_subject'] ?? trim($documentLabel . ' ' . $documentNumber);
        if ($subject === '') {
            $subject = 'Your ' . $documentLabel;
        }
        $this->invoiceData['document'] = array_merge([
            'label' => $documentLabel,
            'number_label' => $document['number_label'] ?? 'Invoice #',
            'email_message_lines' => $document['email_message_lines'] ?? [],
            'email_footer_note' => $document['email_footer_note'] ?? 'Please pay within 7 days.',
        ], $document);
        $email = $this->subject($subject)
            ->view('emails.invoice', $this->invoiceData);

        foreach ($this->extraAttachments as $file) {
            $email->attachData($file['data'], $file['name'], [
                'mime' => $file['mime'] ?? 'application/octet-stream',
            ]);
        }

        return $email;
    }
}
