<?php

use App\Mail\AttendanceAnomalyMail;
use App\Models\CustomerManifest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2032-04-10 20:00:00', 'Africa/Johannesburg'));

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
});

function createAttendanceTeam(array $overrides = []): array
{
    $teamId = DB::table('teams')->insertGetId(array_merge([
        'name' => 'Attendance Test Site',
        'auto_attendance_enabled' => true,
        'auto_attendance_timing' => 'same_day',
        'auto_attendance_run_time' => '20:00:00',
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
    $serviceId = DB::table('services')->insertGetId([
        'name' => "Attendance Activity {$teamId}",
        'capacity' => 8,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $seasonId = DB::table('seasons')->insertGetId([
        'name' => '2032 Attendance Season',
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

    return compact('teamId', 'serviceId', 'seasonId', 'inventoryId');
}

function createAttendanceManifest(
    array $fixture,
    string $activityDate,
    string $attendanceStatus = CustomerManifest::ATTENDANCE_DEFAULT,
    bool $isIndemnity = true,
    bool $expired = false,
    bool $deletedBooking = false,
): int {
    $customerId = DB::table('customers')->insertGetId([
        'full_name' => 'Attendance Guest',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bookingId = DB::table('bookings')->insertGetId([
        'lead_name' => 'Attendance Guest',
        'tot_paid' => 0,
        'grand_tot_due' => 895,
        'reference' => 'REF-ATT-' . fake()->unique()->numerify('######'),
        'customer_id' => $customerId,
        'type_booking_id' => 1,
        'status_payment_id' => 1,
        'payment_window_expired_at' => $expired ? now()->subHour() : null,
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => $deletedBooking ? now() : null,
    ]);
    $bookingItemId = DB::table('booking_items')->insertGetId([
        'booking_id' => $bookingId,
        'service_id' => $fixture['serviceId'],
        'season_id' => $fixture['seasonId'],
        'inventory_id' => $fixture['inventoryId'],
        'pax_adults' => 1,
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
        'date' => $activityDate,
        'adults' => 1,
        'pax' => 1,
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return DB::table('customer_manifests')->insertGetId([
        'type' => 'adult',
        'is_indemnity' => $isIndemnity,
        'attendance_status' => $attendanceStatus,
        'booking_item_id' => $bookingItemId,
        'customer_id' => $customerId,
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

function createAttendanceUser(int $teamId, int $roleId, string $permission): User
{
    DB::table('roles')->insert([
        'id' => $roleId,
        'name' => $roleId === 1 ? 'Admin' : "Site Manager {$roleId}",
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $permissionId = DB::table('permissions')->insertGetId([
        'name' => $permission,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('permission_role')->insert([
        'role_id' => $roleId,
        'permission_id' => $permissionId,
    ]);
    $userId = DB::table('users')->insertGetId([
        'name' => 'Attendance',
        'surname' => 'Manager',
        'email' => "attendance-manager-{$roleId}@example.test",
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('role_user')->insert(['user_id' => $userId, 'role_id' => $roleId]);

    return User::query()->findOrFail($userId);
}

it('marks only eligible completed check-ins for the requested activity date', function () {
    $fixture = createAttendanceTeam();
    $eligible = createAttendanceManifest($fixture, '2032-04-10');
    $arrived = createAttendanceManifest($fixture, '2032-04-10', 'arrived');
    $cancelled = createAttendanceManifest($fixture, '2032-04-10', 'cancelled');
    $incomplete = createAttendanceManifest($fixture, '2032-04-10', 'no_show', false);
    $wrongDate = createAttendanceManifest($fixture, '2032-04-09');
    $expired = createAttendanceManifest($fixture, '2032-04-10', 'no_show', true, true);
    $deleted = createAttendanceManifest($fixture, '2032-04-10', 'no_show', true, false, true);

    $this->artisan('attendance:auto-mark-arrived', [
        '--team' => $fixture['teamId'],
        '--date' => '2032-04-10',
    ])->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($eligible)->attendance_status)->toBe('arrived')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($arrived)->attendance_status)->toBe('arrived')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($cancelled)->attendance_status)->toBe('cancelled')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($incomplete)->attendance_status)->toBe('no_show')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($wrongDate)->attendance_status)->toBe('no_show')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($expired)->attendance_status)->toBe('no_show')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($deleted)->attendance_status)->toBe('no_show');
});

it('leaves disabled sites unchanged', function () {
    $fixture = createAttendanceTeam(['auto_attendance_enabled' => false]);
    $manifestId = createAttendanceManifest($fixture, '2032-04-10');

    $this->artisan('attendance:auto-mark-arrived', [
        '--team' => $fixture['teamId'],
        '--date' => '2032-04-10',
    ])->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($manifestId)->attendance_status)->toBe('no_show');
});

it('supports dry runs and is repeat safe', function () {
    $fixture = createAttendanceTeam();
    $manifestId = createAttendanceManifest($fixture, '2032-04-10');

    $this->artisan('attendance:auto-mark-arrived', [
        '--team' => $fixture['teamId'],
        '--date' => '2032-04-10',
        '--dry-run' => true,
    ])->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($manifestId)->attendance_status)->toBe('no_show');

    $this->artisan('attendance:auto-mark-arrived', [
        '--team' => $fixture['teamId'],
        '--date' => '2032-04-10',
    ])->assertSuccessful();
    $this->artisan('attendance:auto-mark-arrived', [
        '--team' => $fixture['teamId'],
        '--date' => '2032-04-10',
    ])->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($manifestId)->attendance_status)->toBe('arrived');
});

it('uses each enabled sites configured same-day or next-day timing', function () {
    $sameDay = createAttendanceTeam(['name' => 'Same Day Site']);
    $nextDay = createAttendanceTeam([
        'name' => 'Next Day Site',
        'auto_attendance_timing' => 'next_day',
    ]);
    $notDue = createAttendanceTeam([
        'name' => 'Not Due Site',
        'auto_attendance_run_time' => '20:01:00',
    ]);
    $sameDayManifest = createAttendanceManifest($sameDay, '2032-04-10');
    $nextDayManifest = createAttendanceManifest($nextDay, '2032-04-09');
    $notDueManifest = createAttendanceManifest($notDue, '2032-04-10');

    $this->artisan('attendance:auto-mark-arrived')->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($sameDayManifest)->attendance_status)->toBe('arrived')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($nextDayManifest)->attendance_status)->toBe('arrived')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($notDueManifest)->attendance_status)->toBe('no_show');
});

it('emails once about missed eligible arrivals without changing their attendance', function () {
    Mail::fake();
    config(['mail.audit_bcc' => 'attendance-audit@example.test']);

    $fixture = createAttendanceTeam();
    Cache::forget("attendance:missed-arrivals-alert:{$fixture['teamId']}:2032-04-10");
    $manifestId = createAttendanceManifest($fixture, '2032-04-10');
    $this->travelTo(CarbonImmutable::parse('2032-04-10 20:01:00', 'Africa/Johannesburg'));

    $this->artisan('attendance:auto-mark-arrived')->assertSuccessful();
    $this->artisan('attendance:auto-mark-arrived')->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($manifestId)->attendance_status)
        ->toBe('no_show');

    expect(Mail::sent(AttendanceAnomalyMail::class))->toHaveCount(1);
    Mail::assertSent(AttendanceAnomalyMail::class, function (AttendanceAnomalyMail $mail) use ($fixture) {
        return $mail->hasTo('attendance-audit@example.test')
            && $mail->teamId === $fixture['teamId']
            && $mail->activityFrom === '2032-04-10'
            && $mail->activityTo === '2032-04-10'
            && $mail->candidateCount === 1
            && str_contains($mail->render(), 'After reviewing the CSV report');
    });
});

it('emails a report-only digest for historical eligible arrivals', function () {
    Mail::fake();
    config(['mail.audit_bcc' => 'attendance-audit@example.test']);

    $fixture = createAttendanceTeam();
    Cache::forget("attendance:historical-anomaly-scan:{$fixture['teamId']}:2032-04-10");
    Cache::forget("attendance:historical-anomaly-alert:{$fixture['teamId']}");
    $eligible = createAttendanceManifest($fixture, '2032-04-08');
    $createAttendanceManifest = createAttendanceManifest($fixture, '2032-04-07', 'no_show', true, true);
    $this->travelTo(CarbonImmutable::parse('2032-04-10 20:01:00', 'Africa/Johannesburg'));

    $this->artisan('attendance:auto-mark-arrived')->assertSuccessful();
    $this->travelTo(CarbonImmutable::parse('2032-04-11 20:01:00', 'Africa/Johannesburg'));
    $this->artisan('attendance:auto-mark-arrived')->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($eligible)->attendance_status)
        ->toBe('no_show')
        ->and(CustomerManifest::withoutGlobalScopes()->findOrFail($createAttendanceManifest)->attendance_status)
        ->toBe('no_show');

    expect(Mail::sent(AttendanceAnomalyMail::class))->toHaveCount(1);
    Mail::assertSent(AttendanceAnomalyMail::class, function (AttendanceAnomalyMail $mail) use ($fixture) {
        return $mail->hasTo('attendance-audit@example.test')
            && $mail->teamId === $fixture['teamId']
            && $mail->activityFrom === '2032-04-08'
            && $mail->activityTo === '2032-04-08'
            && $mail->candidateCount === 1
            && $mail->historical;
    });
});

it('does not send missed-arrival alerts when the audit recipient is not configured', function () {
    Mail::fake();
    config(['mail.audit_bcc' => null]);

    $fixture = createAttendanceTeam();
    Cache::forget("attendance:missed-arrivals-alert:{$fixture['teamId']}:2032-04-10");
    createAttendanceManifest($fixture, '2032-04-10');
    $this->travelTo(CarbonImmutable::parse('2032-04-10 20:01:00', 'Africa/Johannesburg'));

    $this->artisan('attendance:auto-mark-arrived')->assertFailed();

    Mail::assertNothingSent();
});

it('allows only admin users to change attendance automation settings', function () {
    $fixture = createAttendanceTeam(['auto_attendance_enabled' => false]);
    $siteManager = createAttendanceUser($fixture['teamId'], 2, 'teams_edit');

    $this->actingAs($siteManager)
        ->put(route('admin.teams.update', $fixture['teamId']), [
            'name' => 'Attendance Test Site',
            'auto_attendance_enabled' => 1,
            'auto_attendance_timing' => 'next_day',
            'auto_attendance_run_time' => '21:30',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('teams', [
        'id' => $fixture['teamId'],
        'auto_attendance_enabled' => 0,
        'auto_attendance_timing' => 'same_day',
        'auto_attendance_run_time' => '20:00:00',
    ]);

    DB::table('permission_role')->delete();
    DB::table('role_user')->delete();
    DB::table('permissions')->delete();
    DB::table('roles')->delete();
    $admin = createAttendanceUser($fixture['teamId'], 1, 'teams_edit');

    $this->actingAs($admin)
        ->put(route('admin.teams.update', $fixture['teamId']), [
            'name' => 'Attendance Test Site',
            'auto_attendance_enabled' => 1,
            'auto_attendance_timing' => 'next_day',
            'auto_attendance_run_time' => '21:30',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas('teams', [
        'id' => $fixture['teamId'],
        'auto_attendance_enabled' => 1,
        'auto_attendance_timing' => 'next_day',
        'auto_attendance_run_time' => '21:30:00',
    ]);
});

it('creates a detailed attendance backfill report without updating by default', function () {
    Storage::fake('local');
    $fixture = createAttendanceTeam(['auto_attendance_enabled' => false]);
    $manifestId = createAttendanceManifest($fixture, '2032-04-09');

    $this->artisan('attendance:backfill-arrived', [
        '--team' => $fixture['teamId'],
        '--from' => '2032-04-09',
        '--to' => '2032-04-09',
    ])->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($manifestId)->attendance_status)->toBe('no_show');

    $reports = Storage::disk('local')->files('reports');
    expect($reports)->toHaveCount(1);
    expect(Storage::disk('local')->get($reports[0]))
        ->toContain('manifest_id,team_id,activity_date')
        ->toContain('Attendance Guest')
        ->toContain('mark_arrived');
});

it('applies an attendance backfill with audit history and is repeat safe', function () {
    Storage::fake('local');
    $fixture = createAttendanceTeam(['auto_attendance_enabled' => false]);
    $manifestId = createAttendanceManifest($fixture, '2032-04-09');

    $parameters = [
        '--team' => $fixture['teamId'],
        '--from' => '2032-04-09',
        '--to' => '2032-04-09',
        '--apply' => true,
    ];

    $this->artisan('attendance:backfill-arrived', $parameters)->assertSuccessful();

    expect(CustomerManifest::withoutGlobalScopes()->findOrFail($manifestId)->attendance_status)->toBe('arrived');
    $this->assertDatabaseHas('audit_logs', [
        'description' => 'audit:updated',
        'subject_id' => $manifestId,
        'subject_type' => CustomerManifest::class,
    ]);

    $auditCount = DB::table('audit_logs')
        ->where('description', 'audit:updated')
        ->where('subject_id', $manifestId)
        ->where('subject_type', CustomerManifest::class)
        ->count();

    $this->artisan('attendance:backfill-arrived', $parameters)->assertSuccessful();

    expect(DB::table('audit_logs')
        ->where('description', 'audit:updated')
        ->where('subject_id', $manifestId)
        ->where('subject_type', CustomerManifest::class)
        ->count())->toBe($auditCount);
});

it('rejects invalid and future attendance backfill ranges', function () {
    $fixture = createAttendanceTeam();

    $this->artisan('attendance:backfill-arrived', [
        '--team' => $fixture['teamId'],
        '--from' => '2032-04-10',
        '--to' => '2032-04-09',
    ])->assertFailed();

    $this->artisan('attendance:backfill-arrived', [
        '--team' => $fixture['teamId'],
        '--from' => '2032-04-11',
        '--to' => '2032-04-11',
    ])->assertFailed();
});
