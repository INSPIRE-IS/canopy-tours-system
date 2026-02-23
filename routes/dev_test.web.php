<?php

use App\Http\Controllers\Admin\BookingsController;
use App\Models\BookingItem;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dev/test-boarding-pass-missing-template/{bookingItem}', function (BookingItem $bookingItem) {
        $bookingItem->load('booking', 'booking.team', 'service', 'booking_inventory.inventory_time_slot');

        if (request()->boolean('force_null', true)) {
            $bookingItem->service_id = null;
            $bookingItem->season_id = null;
        }

        $data = app(BookingsController::class)->getPassDetails($bookingItem);

        return response()->json([
            'booking_item_id' => $bookingItem->id,
            'team_id' => $bookingItem->booking->team_id,
            'service_id' => $bookingItem->service_id,
            'season_id' => $bookingItem->season_id,
            'template_applied' => !empty($data['sections']),
            'data' => $data,
        ]);
    });
});
