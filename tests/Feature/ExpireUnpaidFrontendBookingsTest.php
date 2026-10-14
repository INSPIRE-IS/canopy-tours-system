<?php

use App\Models\Booking;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('marks an overdue unpaid frontend booking expired without deleting its records', function () {
    $now = CarbonImmutable::parse('2032-04-10 12:00:00', 'Africa/Johannesburg');
    $this->travelTo($now);

    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Expiry Test Team',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('status_payments')->insert([
        'id' => 1,
        'name' => 'Pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $bookingId = DB::table('bookings')->insertGetId([
        'lead_name' => 'Expired Guest',
        'session_id' => 'frontend-expiry-test',
        'status_payment_id' => 1,
        'tot_paid' => 0,
        'grand_tot_due' => 895,
        'reference' => 'REF-EXPIRED',
        'team_id' => $teamId,
        'created_at' => now()->subHours(4),
        'updated_at' => now()->subHours(4),
    ]);

    $this->artisan('bookings:expire-unpaid-front', ['--hours' => 3, '--force' => true])
        ->assertExitCode(0);

    $booking = Booking::query()->findOrFail($bookingId);

    expect($booking->deleted_at)->toBeNull()
        ->and($booking->payment_window_expired_at?->timestamp)->toBe($now->timestamp)
        ->and(DB::table('audit_logs')
            ->where('subject_type', Booking::class)
            ->where('subject_id', $bookingId)
            ->where('description', 'Frontend unpaid booking payment window expired')
            ->exists())->toBeTrue();
});

it('keeps dry runs non-destructive', function () {
    $now = CarbonImmutable::parse('2032-04-10 12:00:00', 'Africa/Johannesburg');
    $this->travelTo($now);

    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Expiry Dry Run Team',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('status_payments')->insert([
        'id' => 1,
        'name' => 'Pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $bookingId = DB::table('bookings')->insertGetId([
        'lead_name' => 'Dry Run Guest',
        'session_id' => 'frontend-expiry-dry-run',
        'status_payment_id' => 1,
        'tot_paid' => 0,
        'grand_tot_due' => 895,
        'reference' => 'REF-DRY-RUN',
        'team_id' => $teamId,
        'created_at' => now()->subHours(4),
        'updated_at' => now()->subHours(4),
    ]);

    $this->artisan('bookings:expire-unpaid-front', ['--hours' => 3])
        ->assertExitCode(0);

    expect(Booking::query()->findOrFail($bookingId)->payment_window_expired_at)->toBeNull();
});
