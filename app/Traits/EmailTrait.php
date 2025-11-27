<?php

namespace App\Traits;

use App\Mail\TestEmailWithAttachment;
use App\Mail\FeedbackRequestMail;
use Illuminate\Support\Facades\Mail;

trait EmailTrait
{

    public function sendFeedbackEmail($to, $customerName, $tourName, $tourDate, $feedbackLink)
    {
        Mail::to($to)->send(
            new FeedbackRequestMail($customerName, $tourName, $tourDate, $feedbackLink)
        );

        return "Feedback email sent to $to";
    }

    public function sendTestEmail($to)
    {
        Mail::raw('This is a test email from EmailTrait.', function ($message) use ($to) {
            $message->to($to)
                ->subject('Test Email from Trait');
        });

        return "Email sent to $to";
    }

    public function sendTestEmailAttachement($email)
    {
        // Path to the test.pdf file
        $filePath = public_path('test/test.pdf');
        $this->info("filePath {$filePath}");

        // Check if the file exists
        if (!file_exists($filePath)) {
            return "Attachment not found!";
        }

        // Send email with attachment
        Mail::to($email)->send(new TestEmailWithAttachment($filePath));

        return "Email sent to $email with attachment.";
    }
}
