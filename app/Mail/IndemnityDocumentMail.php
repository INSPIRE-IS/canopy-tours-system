<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class IndemnityDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        protected array $data,
        protected string $pdfBytes,
        protected string $fileName
    ) {
    }

    public function build()
    {
        $reference = $this->data['reference'] ?? '';
        $subject = trim('Signed Indemnity ' . $reference);

        return $this->subject($subject)
            ->view('emails.indemnity-document', $this->data)
            ->attachData($this->pdfBytes, $this->fileName, [
                'mime' => 'application/pdf',
            ]);
    }
}
