<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

function createReportPreviewUser(): User
{
    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Report Preview Test Site',
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $userId = DB::table('users')->insertGetId([
        'name' => 'Report',
        'surname' => 'Viewer',
        'email' => 'report-viewer@example.test',
        'email_verified_at' => now(),
        'password' => bcrypt('password'),
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return User::query()->findOrFail($userId);
}

it('renders every supported report as an online HTML preview', function () {
    $user = createReportPreviewUser();
    $reports = [
        'bookings',
        'payments',
        'checkin_manifest',
        'booking_adjustment',
        'marketing_opt_in',
        'franchise_fees',
        'wholesaler_usage',
        'promo_code_usage',
    ];

    foreach ($reports as $report) {
        $response = $this->actingAs($user)->get(route('admin.reports.preview', [
            'report' => $report,
            'start_date' => '2030-01-01',
            'end_date' => '2030-02-28',
        ]));

        $response
            ->assertOk()
            ->assertHeader('content-type', 'text/html; charset=UTF-8')
            ->assertSee('report-preview-toolbar', false);
    }
});

it('rejects an online preview longer than two months', function () {
    $user = createReportPreviewUser();

    $this->actingAs($user)
        ->getJson(route('admin.reports.preview', [
            'report' => 'bookings',
            'start_date' => '2030-01-15',
            'end_date' => '2030-03-16',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('end_date');
});

it('requires both dates for an online preview', function () {
    $user = createReportPreviewUser();

    $this->actingAs($user)
        ->getJson(route('admin.reports.preview', [
            'report' => 'bookings',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['start_date', 'end_date']);
});

it('keeps existing downloads available for ranges longer than two months', function () {
    $user = createReportPreviewUser();

    $this->actingAs($user)
        ->get(route('admin.reports.export', [
            'report' => 'bookings',
            'start_date' => '2030-01-01',
            'end_date' => '2030-12-31',
            'delivery' => 'download',
        ]))
        ->assertOk()
        ->assertHeader('content-disposition');
});
