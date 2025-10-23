<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmailWithAttachment extends Mailable
{
    use SerializesModels;

    protected $filePath;

    // Constructor to receive the file path
    public function __construct($filePath)
    {
        $this->filePath = $filePath;
    }

    public function build()
    {
        return $this->subject('Test Email with Attachment')
            ->view('emails.test') // Optional, create an email view
            ->attach($this->filePath, [
                'as' => 'test.pdf', // Optionally specify the name for the attachment
                'mime' => 'application/pdf', // You can adjust the mime type based on your attachment
            ]);
    }
}
