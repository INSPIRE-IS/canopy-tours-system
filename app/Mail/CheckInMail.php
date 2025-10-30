<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CheckInMail extends Mailable
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
        $email = $this->subject('Your Checkin #' . ($this->invoiceData['invoice']['number'] ?? ''))
            ->view('emails.checkin', $this->invoiceData);

        foreach ($this->extraAttachments as $file) {
            $email->attachData($file['data'], $file['name'], [
                'mime' => $file['mime'] ?? 'application/octet-stream',
            ]);
        }

        return $email;
    }
}
