<?php

namespace App\Exceptions;

use App\Models\BookingItem;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
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
                    $bookingItem = BookingItem::with('booking_inventory.inventory_time_slot')->find($bookingItemId);
                    $activityDate = optional($bookingItem?->booking_inventory)->date;
                    if (!$isFeedback) {
                        $expiresAt = $this->resolveCheckinExpiry($bookingItem);
                        if ($expiresAt && now()->lessThanOrEqualTo($expiresAt)) {
                            $params = array_filter([
                                'booking_item_id' => $request->query('booking_item_id'),
                                'reference' => $request->query('reference'),
                                'customer' => $request->query('customer'),
                                'complete' => $request->query('complete'),
                            ], static fn ($value) => $value !== null && $value !== '');

                            return redirect(URL::temporarySignedRoute(
                                'front.booking-questionnaire.checkin',
                                $expiresAt,
                                $params
                            ));
                        }
                        $activityOver = $expiresAt ? now()->greaterThan($expiresAt) : false;
                    } elseif ($activityDate) {
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

    protected function resolveCheckinExpiry(?BookingItem $bookingItem): ?Carbon
    {
        if (!$bookingItem) {
            return null;
        }

        $bookingItem->loadMissing(['booking_inventory.inventory_time_slot']);
        $bookingInventory = $bookingItem->booking_inventory;
        $bookingDate = $bookingInventory?->getRawOriginal('date') ?? $bookingInventory?->date;
        $startTime = $bookingInventory?->inventory_time_slot?->start_time;

        if (!$bookingDate || !$startTime) {
            return null;
        }

        try {
            $startDateTime = Carbon::parse(trim("{$bookingDate} {$startTime}"), config('app.timezone'));
        } catch (\Throwable $e) {
            return null;
        }

        return $startDateTime->copy()->addHours(3);
    }
}
