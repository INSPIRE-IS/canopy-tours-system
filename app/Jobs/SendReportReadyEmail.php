<?php

namespace App\Jobs;

use App\Mail\ReportReadyMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendReportReadyEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly string $email,
        private readonly string $name,
        private readonly string $downloadUrl,
        private readonly string $reportLabel,
    ) {
    }

    public function handle(): void
    {
        Mail::to($this->email)->send(new ReportReadyMail(
            $this->name,
            $this->downloadUrl,
            $this->reportLabel
        ));
    }
}
