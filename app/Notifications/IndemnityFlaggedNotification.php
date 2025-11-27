<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IndemnityFlaggedNotification extends Notification
{
    use Queueable;

    public $customer;
    public $bookingItem;
    public $flagged;   // array of flagged answers
    public $ccEmails;  // optional

    public function __construct($customer, $bookingItem, array $flagged, array $ccEmails = [])
    {
        $this->customer = $customer;
        $this->bookingItem = $bookingItem;
        $this->flagged = $flagged;
        $this->ccEmails = $ccEmails;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('⚠️ Indemnity Alert: A Flagged Response Was Submitted')
            ->view(
                'emails.indemnity-flagged',
                [
                    'customer'     => $this->customer,
                    'bookingItem'  => $this->bookingItem,
                    'flagged'      => $this->flagged,
                ]
            );

        // Add CC recipients if provided
        if (!empty($this->ccEmails)) {
            foreach ($this->ccEmails as $cc) {
                $mail->cc($cc);
            }
        }

        return $mail;
    }
}
