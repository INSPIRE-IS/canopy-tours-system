<?php

namespace App\Traits;

use App\Mail\TestEmailWithAttachment;
use Illuminate\Support\Facades\Mail;

trait EmailTrait
{
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
