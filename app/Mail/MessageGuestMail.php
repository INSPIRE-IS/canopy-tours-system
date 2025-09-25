<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MessageGuestMail extends Mailable
{
    use Queueable, SerializesModels;
    public $subjectLine;
    public $htmlContent;
    public $textContent;

    public function __construct(string $subjectLine, string $htmlContent)
    {
        $this->subjectLine = $subjectLine;
        $this->htmlContent = $htmlContent;
        $this->textContent = strip_tags($htmlContent); // fallback plain text
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->subjectLine)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.message_guest_html') // HTML version
            ->text('emails.message_guest_text'); // Plain-text fallback
    }
}
