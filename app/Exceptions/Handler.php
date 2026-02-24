<?php

namespace App\Exceptions;

use App\Models\BookingItem;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception)
    {
        //dd($exception);
        if ($exception instanceof InvalidSignatureException) {
            if ($request->is('front/booking-questionnaire/checkin') || $request->is('front/feedback-questionnaire/feedback')) {
                $bookingItemId = $request->query('booking_item_id');
                $activityDate = null;
                $activityOver = false;
                $isFeedback = $request->is('front/feedback-questionnaire/feedback');

                if ($bookingItemId) {
                    $bookingItem = BookingItem::with('booking_inventory')->find($bookingItemId);
                    $activityDate = optional($bookingItem?->booking_inventory)->date;
                    if ($activityDate) {
                        $activityOver = Carbon::parse($activityDate)->endOfDay()->isPast();
                    }
                }

                $linkLabel = $isFeedback ? 'feedback' : 'check-in';
                $message = $activityOver
                    ? "This {$linkLabel} link has expired and the activity has already passed."
                    : "This {$linkLabel} link has expired. Please contact your booking agent for a new link.";

                return Inertia::render('Front/BookingQuestionnaire/Expired', [
                    'message' => $message,
                    'activityDate' => $activityDate,
                    'activityOver' => $activityOver,
                ]);
            }

            return redirect()->route('booking-questionnaire.index');
        }

        return parent::render($request, $exception);
    }
}
