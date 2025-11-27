<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedbackRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $tourName;
    public $tourDate;
    public $feedbackLink;

    public function __construct($customerName, $tourName, $tourDate, $feedbackLink)
    {
        $this->customerName = $customerName;
        $this->tourName = $tourName;
        $this->tourDate = $tourDate;
        $this->feedbackLink = $feedbackLink;
    }

    public function build()
    {
        return $this->subject('We’d Love Your Feedback on Your Recent Experience!')
            ->view('emails.feedback-request');
    }
}
