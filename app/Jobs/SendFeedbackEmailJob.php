<?php

namespace App\Jobs;

use App\Mail\FeedbackRequestMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendFeedbackEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $to;
    public $customerName;
    public $tourName;
    public $tourDate;
    public $feedbackLink;

    public function __construct($to, $customerName, $tourName, $tourDate, $feedbackLink)
    {
        $this->to = $to;
        $this->customerName = $customerName;
        $this->tourName = $tourName;
        $this->tourDate = $tourDate;
        $this->feedbackLink = $feedbackLink;
    }

    public function handle()
    {
        Mail::to($this->to)->send(
            new FeedbackRequestMail(
                $this->customerName,
                $this->tourName,
                $this->tourDate,
                $this->feedbackLink
            )
        );
    }
}
