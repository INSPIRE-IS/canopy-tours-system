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

it('never expires admin-created or paid frontend bookings', function () {
    $now = CarbonImmutable::parse('2032-04-10 12:00:00', 'Africa/Johannesburg');
    $this->travelTo($now);

    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Expiry Exclusions Team',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Admin Booking User',
        'surname' => 'Test',
        'email' => 'expiry-admin@example.test',
        'password' => bcrypt('password'),
        'team_id' => $teamId,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('status_payments')->insert([
        [
            'id' => 1,
            'name' => 'Pending',
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'id' => 2,
            'name' => 'Paid',
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    $common = [
        'tot_paid' => 0,
        'grand_tot_due' => 895,
        'team_id' => $teamId,
        'created_at' => now()->subHours(4),
        'updated_at' => now()->subHours(4),
    ];
    $adminBookingId = DB::table('bookings')->insertGetId($common + [
        'lead_name' => 'Admin Guest',
        'user_id' => $userId,
        'status_payment_id' => 1,
        'reference' => 'REF-ADMIN',
    ]);
    $paidFrontendBookingId = DB::table('bookings')->insertGetId($common + [
        'lead_name' => 'Paid Frontend Guest',
        'session_id' => 'paid-frontend-session',
        'status_payment_id' => 2,
        'tot_paid' => 895,
        'reference' => 'REF-PAID',
    ]);

    $this->artisan('bookings:expire-unpaid-front', ['--hours' => 3, '--force' => true])
        ->assertExitCode(0);

    expect(Booking::query()->findOrFail($adminBookingId)->payment_window_expired_at)->toBeNull()
        ->and(Booking::query()->findOrFail($paidFrontendBookingId)->payment_window_expired_at)->toBeNull();
});

it('uses each site expiry setting and treats zero as never expire', function () {
    $now = CarbonImmutable::parse('2032-04-10 12:00:00', 'Africa/Johannesburg');
    $this->travelTo($now);

    $expiringTeamId = DB::table('teams')->insertGetId([
        'name' => 'Three Hour Expiry Team',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $neverExpireTeamId = DB::table('teams')->insertGetId([
        'name' => 'Never Expire Team',
        'frontend_unpaid_booking_hold_hours' => 0,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    DB::table('status_payments')->insert([
        'id' => 1,
        'name' => 'Pending',
        'created_at' => $now,
        'updated_at' => $now,
    ]);

    $booking = [
        'status_payment_id' => 1,
        'tot_paid' => 0,
        'grand_tot_due' => 895,
        'created_at' => now()->subHours(4),
        'updated_at' => now()->subHours(4),
    ];
    $expiringBookingId = DB::table('bookings')->insertGetId($booking + [
        'lead_name' => 'Expiring Guest',
        'session_id' => 'site-expiry-enabled',
        'reference' => 'REF-SITE-EXPIRY',
        'team_id' => $expiringTeamId,
    ]);
    $neverExpireBookingId = DB::table('bookings')->insertGetId($booking + [
        'lead_name' => 'Never Expire Guest',
        'session_id' => 'site-expiry-disabled',
        'reference' => 'REF-SITE-NEVER',
        'team_id' => $neverExpireTeamId,
    ]);

    $this->artisan('bookings:expire-unpaid-front', ['--hours' => 1, '--force' => true])
        ->assertExitCode(0);

    expect(Booking::query()->findOrFail($expiringBookingId)->payment_window_expired_at)->not->toBeNull()
        ->and(Booking::query()->findOrFail($neverExpireBookingId)->payment_window_expired_at)->toBeNull();
});
