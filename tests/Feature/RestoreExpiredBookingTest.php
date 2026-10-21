<?php

use App\Models\Booking;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2032-04-10 08:00:00', 'Africa/Johannesburg'));
});

function createExpiredRestoreFixture(int $capacity = 8): array
{
    DB::table('type_bookings')->insert([
        'id' => 1,
        'name' => 'Standard',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('status_payments')->insert([
        ['id' => 1, 'name' => 'Pending', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Paid', 'created_at' => now(), 'updated_at' => now()],
    ]);

    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Expired Restore Team',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $roleId = DB::table('roles')->insertGetId([
        'name' => 'Expired Booking Manager',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $permissionId = DB::table('permissions')->insertGetId([
        'name' => 'bookings_edit',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('permission_role')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Expiry',
        'surname' => 'Manager',
        'email' => 'expiry-restore-manager@example.test',
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('role_user')->insert([
        'user_id' => $userId,
        'role_id' => $roleId,
    ]);

    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Expired Restore Activity',
        'capacity' => $capacity,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $seasonId = DB::table('seasons')->insertGetId([
        'name' => '2032 Restore Season',
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'rake_rate_adult' => 895,
        'rake_rate_child' => 795,
        'rake_rate_guide' => 0,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $inventoryId = DB::table('inventories')->insertGetId([
        'service_id' => $serviceId,
        'season_id' => $seasonId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $slotId = DB::table('inventory_time_slots')->insertGetId([
        'start_time' => '09:00:00',
        'inventory_id' => $inventoryId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [
        'teamId' => $teamId,
        'user' => User::query()->findOrFail($userId),
        'serviceId' => $serviceId,
        'seasonId' => $seasonId,
        'inventoryId' => $inventoryId,
        'slotId' => $slotId,
    ];
}

function createRestoreTestBooking(array $fixture, string $reference, int $pax, string $date, bool $expired): int
{
    $bookingId = DB::table('bookings')->insertGetId([
        'lead_name' => $reference,
        'session_id' => $expired ? "public-{$reference}" : null,
        'type_booking_id' => 1,
        'status_payment_id' => 1,
        'payment_window_expired_at' => $expired ? now()->subHour() : null,
        'tot_paid' => 0,
        'grand_tot_due' => 895 * $pax,
        'reference' => $reference,
        'team_id' => $fixture['teamId'],
        'created_at' => now()->subHours(4),
        'updated_at' => now()->subHours(4),
    ]);
    $bookingItemId = DB::table('booking_items')->insertGetId([
        'booking_id' => $bookingId,
        'service_id' => $fixture['serviceId'],
        'season_id' => $fixture['seasonId'],
        'inventory_id' => $fixture['inventoryId'],
        'pax_adults' => $pax,
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bookingDetailId = DB::table('booking_details')->insertGetId([
        'booking_item_id' => $bookingItemId,
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('booking_inventories')->insert([
        'booking_item_id' => $bookingItemId,
        'booking_detail_id' => $bookingDetailId,
        'date' => $date,
        'adults' => $pax,
        'pax' => $pax,
        'inventory_time_slot_id' => $fixture['slotId'],
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return $bookingId;
}

it('restores an expired booking as staff managed and prevents automatic re-expiry', function () {
    $fixture = createExpiredRestoreFixture(8);
    $bookingId = createRestoreTestBooking($fixture, 'REF-RESTORE', 2, '2032-04-10', true);
    createRestoreTestBooking($fixture, 'REF-ACTIVE', 4, '2032-04-10', false);
    createRestoreTestBooking($fixture, 'REF-OTHER-EXPIRED', 8, '2032-04-10', true);

    $this->actingAs($fixture['user'])
        ->post(route('admin.bookings.restore-expired', $bookingId))
        ->assertRedirect()
        ->assertSessionHas('success');

    $booking = Booking::query()->findOrFail($bookingId);
    expect($booking->payment_window_expired_at)->toBeNull()
        ->and($booking->payment_window_restored_at)->not->toBeNull()
        ->and($booking->updated_by_id)->toBe($fixture['user']->id)
        ->and(DB::table('audit_logs')
            ->where('subject_type', Booking::class)
            ->where('subject_id', $bookingId)
            ->where('description', 'Expired booking restored by staff')
            ->where('user_id', $fixture['user']->id)
            ->exists())->toBeTrue();

    $this->travel(4)->hours();
    $this->artisan('bookings:expire-unpaid-front', ['--hours' => 1, '--force' => true])
        ->assertExitCode(0);

    expect(Booking::query()->findOrFail($bookingId)->payment_window_expired_at)->toBeNull();
});

it('blocks restoration when active bookings already consume the remaining capacity', function () {
    $fixture = createExpiredRestoreFixture(6);
    $bookingId = createRestoreTestBooking($fixture, 'REF-FULL', 2, '2032-04-10', true);
    createRestoreTestBooking($fixture, 'REF-ACTIVE-FULL', 5, '2032-04-10', false);

    $this->actingAs($fixture['user'])
        ->post(route('admin.bookings.restore-expired', $bookingId))
        ->assertRedirect()
        ->assertSessionHas('error', 'Selected time slot does not have enough capacity. Available capacity: 1.');

    $booking = Booking::query()->findOrFail($bookingId);
    expect($booking->payment_window_expired_at)->not->toBeNull()
        ->and($booking->payment_window_restored_at)->toBeNull()
        ->and($booking->updated_by_id)->toBeNull();
});

it('blocks restoration after the activity date has passed', function () {
    $fixture = createExpiredRestoreFixture();
    $bookingId = createRestoreTestBooking($fixture, 'REF-PAST', 2, '2032-04-09', true);

    $this->actingAs($fixture['user'])
        ->post(route('admin.bookings.restore-expired', $bookingId))
        ->assertRedirect()
        ->assertSessionHas('error', 'This booking cannot be restored because its activity date has passed.');

    expect(Booking::query()->findOrFail($bookingId)->payment_window_expired_at)->not->toBeNull();
});

it('requires booking edit permission to restore an expired booking', function () {
    $fixture = createExpiredRestoreFixture();
    $bookingId = createRestoreTestBooking($fixture, 'REF-NO-PERMISSION', 2, '2032-04-10', true);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Read Only',
        'surname' => 'User',
        'email' => 'expired-read-only@example.test',
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs(User::query()->findOrFail($userId))
        ->post(route('admin.bookings.restore-expired', $bookingId))
        ->assertForbidden();

    expect(Booking::query()->findOrFail($bookingId)->payment_window_expired_at)->not->toBeNull();
});
