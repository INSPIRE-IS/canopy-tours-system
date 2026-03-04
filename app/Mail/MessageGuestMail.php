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
    public $attachmentsList;

    public function __construct(string $subjectLine, string $htmlContent, array $attachments = [])
    {
        $this->subjectLine = $subjectLine;
        $this->htmlContent = $htmlContent;
        $this->textContent = strip_tags($htmlContent); // fallback plain text
        $this->attachmentsList = $attachments;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $mail = $this->subject($this->subjectLine)
            ->from(config('mail.from.address'), config('mail.from.name'))
            ->view('emails.message_guest_html') // HTML version
            ->text('emails.message_guest_text'); // Plain-text fallback

        foreach ($this->attachmentsList as $attachment) {
            if (is_array($attachment) && !empty($attachment['data'])) {
                $mail->attachData($attachment['data'], $attachment['name'] ?? 'attachment', [
                    'mime' => $attachment['mime'] ?? 'application/octet-stream',
                ]);
                continue;
            }

            if (is_string($attachment) && file_exists($attachment)) {
                $mail->attach($attachment);
            }
        }

        return $mail;
    }
}
