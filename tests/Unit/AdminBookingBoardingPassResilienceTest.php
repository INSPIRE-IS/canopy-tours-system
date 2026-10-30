<?php

use App\Http\Controllers\Admin\BookingsController;
use App\Models\Booking;
use App\Models\BookingItem;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;

it('skips automatic boarding-pass generation when the production hotfix switch is disabled', function () {
    app()->instance('config', new Repository([
        'browsershot' => ['admin_booking_boarding_pass_enabled' => false],
    ]));
    Facade::setFacadeApplication(app());

    $booking = (new ReflectionClass(Booking::class))->newInstanceWithoutConstructor();
    $booking->id = 123;
    $bookingItem = (new ReflectionClass(BookingItem::class))->newInstanceWithoutConstructor();
    $bookingItem->id = 456;
    $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('disabled-admin-booking-pass-', true) . '.pdf';

    $logger = Mockery::mock();
    $logger->shouldReceive('warning')
        ->once()
        ->with(
            'Automatic admin booking boarding-pass generation is disabled; booking will continue.',
            ['booking_id' => 123, 'booking_item_id' => 456]
        );
    app()->instance('log', $logger);

    $method = new ReflectionMethod(BookingsController::class, 'tryGenerateBoardingPassPdf');
    $method->setAccessible(true);

    $generated = $method->invoke(
        new BookingsController(),
        '<html><body>Boarding pass</body></html>',
        $outputPath,
        $booking,
        $bookingItem
    );

    expect($generated)->toBeFalse()
        ->and(is_file($outputPath))->toBeFalse();
});
