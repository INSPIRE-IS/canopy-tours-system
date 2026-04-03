<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly string $downloadUrl,
        public readonly string $reportLabel,
    ) {
    }

    public function build(): self
    {
        return $this->subject("{$this->reportLabel} report ready")
            ->view('emails.report-ready');
    }
}
