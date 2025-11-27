<?php

namespace App\Console\Commands;

use App\Jobs\SendFeedbackEmailJob;
use Illuminate\Console\Command;
use App\Models\BookingItem;
use App\Models\Email;
use Illuminate\Support\Facades\URL;
use App\Traits\EmailTrait;
use Illuminate\Support\Facades\Log;

class SendFeedbackEmail extends Command
{

    use EmailTrait;

    protected $signature = 'app:send-feedback-email';
    // Run the queue worker
    // In development:
    // php artisan queue:work
    // Or, if you want it to auto-restart on file changes:
    // php artisan queue:listen 
    // Testing environment settings
    // QUEUE_CONNECTION=sync
    // QUEUE_CONNECTION=database
    // QUEUE_CONNECTION=redis

    // php artisan app:send-feedback-email
    protected $description = 'Send feedback emails to customers 2 days after their tour';

    public function handle()
    {
        $twoDaysAgo = now()->subDays(2)->toDateString();

        $this->info("Checking bookings for: " . $twoDaysAgo);

        $bookingItems = BookingItem::with(['customers', 'booking_inventory'])
            ->whereHas('booking_inventory', function ($query) use ($twoDaysAgo) {
                // $query->whereDate('date', $twoDaysAgo);
                $query->whereDate('date', '<=', $twoDaysAgo);
            })
            ->get();

        if ($bookingItems->isEmpty()) {
            $this->info('No completed bookings found for that date.');
            return;
        }

        foreach ($bookingItems as $bookingItem) {

            foreach ($bookingItem->customers as $customer) {

                if (!$customer->email_address) {
                    Log::warning("Customer {$customer->id} has no email.");
                    continue;
                }
                // 🔍 Check if feedback email already sent
                $alreadySent = Email::where('auditable_type', 'feedback')
                    ->where('auditable_id', $bookingItem->id)
                    ->where('receiver', $customer->email_address)
                    ->where('sent', true)
                    ->exists();
                if ($alreadySent) {
                    $this->info("Feedback email already sent to {$customer->email_address}, skipping.");
                    continue;
                }
                // Build signed feedback URL (valid for 7 days)
                $feedbackUrl = URL::temporarySignedRoute(
                    'front.feedback-questionnaire.feedback',
                    now()->addDays(7),
                    [
                        'booking_item_id' => $bookingItem->id,
                        'customer_id'     => $customer->id,
                    ]
                );

                // Send email using your trait
                // $result = $this->sendFeedbackEmail(
                //     $customer->email,
                //     $customer->name,
                //     $bookingItem->booking_inventory->name ?? 'Your Tour',
                //     $bookingItem->booking_inventory->date ?? null,
                //     $feedbackUrl
                // );
                Log::info("Feedback email sent to {$customer->email_address}");
                Log::info("Feedback email sent to {$feedbackUrl}");
                SendFeedbackEmailJob::dispatch(
                    $customer->email_address,
                    $customer->name,
                    $bookingItem->booking_inventory->name ?? 'Your Tour',
                    $bookingItem->booking_inventory->date ?? null,
                    $feedbackUrl
                );

                // Log the email in DB
                Email::create([
                    'sender'         => config('mail.from.address'),
                    'receiver'       => $customer->email_address,
                    'subject'        => 'Tour Feedback Request',
                    'body'           => 'Feedback email sent (HTML email stored separately). ' . $feedbackUrl,
                    'sent'           => true,
                    'sent_at'        => now(),
                    'auditable_type' => 'feedback',
                    'auditable_id'   => $bookingItem->id,
                    'user_id'        => null,
                    'team_id'        => $bookingItem->team_id ?? null,
                ]);
                Log::info("Feedback email sent to {$customer->email_address}");
                $this->info("Feedback email sent to {$customer->email_address}");
            }
        }

        $this->info('All feedback emails dispatched.');
    }
}
