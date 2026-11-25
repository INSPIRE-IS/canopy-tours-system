<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendanceAnomalyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $teamName,
        public readonly int $teamId,
        public readonly string $activityFrom,
        public readonly string $activityTo,
        public readonly int $candidateCount,
        public readonly array $activityDateCounts,
        public readonly bool $historical = false,
    ) {
    }

    public function build(): self
    {
        return $this->subject("Attendance anomaly: {$this->teamName} ({$this->activityFrom} to {$this->activityTo})")
            ->text('emails.attendance-anomaly');
    }
}
