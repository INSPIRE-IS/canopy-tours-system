<?php

use App\Models\Booking;
use App\Http\Controllers\Front\PaymentController;
use App\Exports\Reports\BookingsReportExport;
use App\Jobs\FulfillBookingPayment;
use App\Mail\BookingMail;
use App\Models\BookingItem;
use App\Models\PublicBookingHold;
use App\Services\EmailDispatchService;
use App\Services\PurchaseAnalyticsService;
use App\Services\RendererService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(fn () => CarbonImmutable::setTestNow('2032-04-01 09:00:00'));
afterEach(fn () => CarbonImmutable::setTestNow());

function createV2CheckoutCompletionFixture(): array
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
    DB::table('type_invoices')->insert([
        'id' => 1,
        'name' => 'Standard',
        'slug' => 'std',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('type_files')->insert([
        ['id' => 4, 'name' => 'Invoice', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 6, 'name' => 'Boarding pass', 'created_at' => now(), 'updated_at' => now()],
    ]);
    foreach (range(1, 7) as $dayId) {
        DB::table('type_days')->insert([
            'id' => $dayId,
            'name' => CarbonImmutable::now()->startOfWeek()->addDays($dayId - 1)->format('l'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Checkout Completion Site',
        'is_published' => true,
        'frontend_hold_enabled' => true,
        'frontend_hold_minutes' => 15,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Canopy Tour',
        'capacity' => 8,
        'is_online_bookable' => true,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $seasonId = DB::table('seasons')->insertGetId([
        'name' => '2032',
        'start_date' => '2032-01-01',
        'end_date' => '2032-12-31',
        'rake_rate_adult' => 895,
        'rake_rate_child' => 795,
        'rake_rate_guide' => 0,
        'allow_internal_price_override' => false,
        'internal_only' => false,
        'include_public_extras' => true,
        'status' => true,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $inventoryId = DB::table('inventories')->insertGetId([
        'allow_internal_override' => false,
        'slot_cutoff_hours' => 0,
        'slot_funneling' => 0,
        'service_id' => $serviceId,
        'season_id' => $seasonId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    foreach (range(1, 7) as $dayId) {
        DB::table('inventory_type_days')->insert([
            'inventory_id' => $inventoryId,
            'type_day_id' => $dayId,
            'team_id' => $teamId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    $slotId = DB::table('inventory_time_slots')->insertGetId([
        'start_time' => '09:00:00',
        'is_override_only' => false,
        'inventory_id' => $inventoryId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $extraId = DB::table('season_extras')->insertGetId([
        'season_id' => $seasonId,
        'team_id' => $teamId,
        'name' => 'Lunch',
        'description' => 'Lunch after the tour',
        'unit_price' => 55,
        'is_per_guest' => true,
        'is_active' => true,
        'sort_order' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $otherTeamId = DB::table('teams')->insertGetId([
        'name' => 'Other Checkout Site',
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('invoice_templates')->insert([
        'name' => 'Other Site Invoice',
        'content' => '<p>Wrong tenant invoice</p>',
        'is_default' => true,
        'type_invoice_id' => 1,
        'team_id' => $otherTeamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $invoiceTemplateId = DB::table('invoice_templates')->insertGetId([
        'name' => 'Standard Invoice',
        'content' => '<p>Invoice</p>',
        'is_default' => true,
        'type_invoice_id' => 1,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $holdSessionKey = (string) Str::uuid();
    $hold = PublicBookingHold::create([
        'uuid' => (string) Str::uuid(),
        'session_id' => $holdSessionKey,
        'team_id' => $teamId,
        'service_id' => $serviceId,
        'season_id' => $seasonId,
        'inventory_id' => $inventoryId,
        'inventory_time_slot_id' => $slotId,
        'booking_date' => '2032-04-10',
        'adults' => 2,
        'children' => 1,
        'expires_at' => now()->addMinutes(15),
    ]);

    return compact(
        'teamId',
        'serviceId',
        'seasonId',
        'inventoryId',
        'slotId',
        'extraId',
        'invoiceTemplateId',
        'holdSessionKey',
        'hold'
    );
}

function v2CheckoutCompletionPayload(array $fixture): array
{
    $terms = "Conditions and cancellation policy\n\nPlease contact us if you need a copy of the activity conditions and cancellation policy.";

    return [
        'hold_uuid' => $fixture['hold']->uuid,
        'full_name' => 'Checkout Guest',
        'email' => 'guest@example.com',
        'phone' => '+27821234567',
        'extra_ids' => [$fixture['extraId']],
        'terms_accepted' => true,
        'terms_hash' => hash('sha256', $terms),
    ];
}

function createPaidV2CheckoutBooking($test, array $fixture): Booking
{
    $bookingId = $test
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertOk()
        ->json('booking_id');

    $booking = Booking::findOrFail($bookingId);
    $booking->update([
        'status_payment_id' => 2,
        'tot_paid' => $booking->grand_tot_due,
    ]);

    return $booking->refresh();
}

function storeExistingBoardingPass(Booking $booking, ?BookingItem $bookingItem = null): void
{
    $bookingItem ??= $booking->booking_items()->firstOrFail();
    $path = "pass/{$bookingItem->id}/boarding_pass_{$bookingItem->id}.pdf";
    Storage::disk('public')->put($path, 'existing boarding pass');
    DB::table('files')->insert([
        'table_name' => 'BookingItem',
        'table_row_id' => (string) $bookingItem->id,
        'file_name' => basename($path),
        'file_type' => 'application/pdf',
        'path' => $path,
        'team_id' => $booking->team_id,
        'type_file_id' => 6,
        'booking_id' => $booking->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('returns booking payments to the owning V2 site after success or cancellation', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $booking = createPaidV2CheckoutBooking($this, $fixture);
    $purchaseContext = app(PurchaseAnalyticsService::class)
        ->issueContextToken('booking', $booking->id);
    $homeUrl = route('front.welcome', ['team' => $fixture['teamId']]);

    foreach (['front.payment.payfast.return', 'front.payment.payfast.cancel'] as $routeName) {
        $this->get(route($routeName, ['purchase_context' => $purchaseContext]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('PayfastResult')
                ->where('site.id', $fixture['teamId'])
                ->where('site.name', 'Checkout Completion Site')
                ->where('homeUrl', $homeUrl)
                ->etc());
    }
});

it('exposes a paid core activity purchase without extras or customer data', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $booking = createPaidV2CheckoutBooking($this, $fixture);
    $analytics = app(PurchaseAnalyticsService::class);
    $purchaseContext = $analytics->issueContextToken('booking', $booking->id);

    expect((float) $booking->grand_tot_due)->toBe(2750.0);

    $response = $this->getJson(route('front.payment.payfast.purchase-status', [
        'purchase_context' => $purchaseContext,
    ]))->assertOk();

    $response
        ->assertJsonPath('state', 'paid')
        ->assertJsonPath('purchase.event', 'purchase')
        ->assertJsonPath('purchase.ecommerce.transaction_id', 'booking-' . $booking->reference)
        ->assertJsonPath('purchase.ecommerce.value', 2585)
        ->assertJsonPath('purchase.ecommerce.currency', 'ZAR')
        ->assertJsonPath('purchase.ecommerce.items.0.item_name', 'Canopy Tour')
        ->assertJsonPath('purchase.ecommerce.items.0.affiliation', 'Checkout Completion Site')
        ->assertJsonPath('purchase.ecommerce.items.0.price', 2585)
        ->assertJsonCount(1, 'purchase.ecommerce.items');

    expect($response->getContent())
        ->not->toContain('guest@example.com')
        ->not->toContain('+27821234567')
        ->not->toContain('Checkout Guest');

    $this->postJson(route('front.payment.payfast.purchase-acknowledged'), [
        'purchase_context' => $purchaseContext,
        'transaction_id' => 'booking-' . $booking->reference,
    ])->assertNoContent();
});

it('does not expose a purchase event before the booking is paid', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $bookingId = $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertOk()
        ->json('booking_id');
    $purchaseContext = app(PurchaseAnalyticsService::class)
        ->issueContextToken('booking', $bookingId);

    $this->getJson(route('front.payment.payfast.purchase-status', [
        'purchase_context' => $purchaseContext,
    ]))
        ->assertOk()
        ->assertJsonPath('state', 'pending')
        ->assertJsonPath('purchase', null);

    $this->postJson(route('front.payment.payfast.purchase-acknowledged'), [
        'purchase_context' => $purchaseContext,
        'transaction_id' => 'booking-not-paid',
    ])->assertUnprocessable();
});

it('gates purchase diagnostic logs without changing the analytics response', function (bool $loggingEnabled) {
    $fixture = createV2CheckoutCompletionFixture();
    $booking = createPaidV2CheckoutBooking($this, $fixture);
    $purchaseContext = app(PurchaseAnalyticsService::class)
        ->issueContextToken('booking', $booking->id);

    config()->set('services.google_tag_manager.purchase_logging_enabled', $loggingEnabled);
    Log::spy();

    $this->getJson(route('front.payment.payfast.purchase-status', [
        'purchase_context' => $purchaseContext,
    ]))
        ->assertOk()
        ->assertJsonPath('purchase.ecommerce.transaction_id', 'booking-' . $booking->reference);

    $this->postJson(route('front.payment.payfast.purchase-acknowledged'), [
        'purchase_context' => $purchaseContext,
        'transaction_id' => 'booking-' . $booking->reference,
    ])->assertNoContent();

    if ($loggingEnabled) {
        Log::shouldHaveReceived('info')
            ->with('GA4 purchase payload ready', Mockery::type('array'))
            ->once();
        Log::shouldHaveReceived('info')
            ->with('GA4 purchase pushed to browser dataLayer', Mockery::type('array'))
            ->once();

        return;
    }

    Log::shouldNotHaveReceived('info', [
        'GA4 purchase payload ready',
        Mockery::type('array'),
    ]);
    Log::shouldNotHaveReceived('info', [
        'GA4 purchase pushed to browser dataLayer',
        Mockery::type('array'),
    ]);
})->with([
    'logging disabled' => false,
    'logging enabled' => true,
]);

it('atomically converts an owned hold into one provisional booking with extras and legal acceptance', function () {
    $fixture = createV2CheckoutCompletionFixture();

    $response = $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertOk()
        ->assertJson(['success' => true]);

    $booking = Booking::with(['booking_items.booking_fee', 'booking_extras', 'terms_acceptance'])
        ->findOrFail($response->json('booking_id'));

    expect((float) $booking->grand_tot_due)->toBe(2750.0)
        ->and((float) $booking->booking_items->first()->booking_fee->tot_amount)->toBe(2585.0)
        ->and($booking->booking_extras)->toHaveCount(1)
        ->and((float) $booking->booking_extras->first()->quantity)->toBe(3.0)
        ->and((float) $booking->booking_extras->first()->total_amount)->toBe(165.0)
        ->and($booking->terms_acceptance)->not->toBeNull()
        ->and($booking->terms_acceptance->wording_hash)->toBe(v2CheckoutCompletionPayload($fixture)['terms_hash']);

    $this->assertDatabaseHas('invoices', [
        'booking_id' => $booking->id,
        'invoice_template_id' => $fixture['invoiceTemplateId'],
        'total' => 2750.00,
    ]);
    $this->assertDatabaseHas('public_booking_holds', [
        'id' => $fixture['hold']->id,
        'booking_id' => $booking->id,
    ]);
});

it('creates a correctly priced child-only provisional booking', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $fixture['hold']->update(['adults' => 0, 'children' => 1]);

    $bookingId = $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertOk()
        ->json('booking_id');

    $booking = Booking::query()->findOrFail($bookingId);
    $item = $booking->booking_items()->firstOrFail();

    expect($item->pax_adults)->toBe(0)
        ->and($item->pax_children)->toBe(1)
        ->and((float) $item->booking_fee->tot_adults)->toBe(0.0)
        ->and((float) $item->booking_fee->tot_children)->toBe(795.0)
        ->and((float) $booking->grand_tot_due)->toBe(850.0);
});

it('rejects final confirmation when an invalid hold contains no guests', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $fixture['hold']->update(['adults' => 0, 'children' => 0]);

    $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('adults');

    $this->assertDatabaseCount('bookings', 0);
    $this->assertDatabaseCount('customers', 0);
});

it('creates the invoice when the site has no default invoice template', function () {
    $fixture = createV2CheckoutCompletionFixture();
    DB::table('invoice_templates')->where('team_id', $fixture['teamId'])->delete();

    $response = $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertOk();

    $this->assertDatabaseHas('invoices', [
        'booking_id' => $response->json('booking_id'),
        'invoice_template_id' => null,
        'type_invoice_id' => 1,
        'total' => 2750.00,
    ]);
});

it('prepares paid booking documents without sending email when automatic payment email is disabled', function () {
    Mail::fake();
    Storage::fake('public');
    $fixture = createV2CheckoutCompletionFixture();
    $booking = createPaidV2CheckoutBooking($this, $fixture);
    storeExistingBoardingPass($booking);
    DB::table('files')
        ->where('booking_id', $booking->id)
        ->where('type_file_id', 6)
        ->update(['deleted_at' => now()]);
    $emailDispatch = Mockery::mock(EmailDispatchService::class);
    $emailDispatch->shouldReceive('canSendAutomatic')
        ->once()
        ->with($fixture['teamId'], EmailDispatchService::SETTING_AUTO_PAYMENT)
        ->andReturnFalse();

    (new FulfillBookingPayment($booking->id))->handle($emailDispatch, app(RendererService::class));

    $this->assertDatabaseCount('emails', 0);
    $this->assertDatabaseHas('files', [
        'booking_id' => $booking->id,
        'type_file_id' => 4,
        'table_name' => 'invoices',
    ]);
    expect(DB::table('files')->where('booking_id', $booking->id)->count())->toBe(2);
    $this->assertDatabaseHas('files', [
        'booking_id' => $booking->id,
        'type_file_id' => 6,
        'deleted_at' => null,
    ]);
    $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status_payment_id' => 2]);
    Mail::assertNothingSent();
});

it('uses the same deferred fulfilment path for a simulated booking payment', function () {
    Mail::fake();
    Storage::fake('public');
    $fixture = createV2CheckoutCompletionFixture();
    $bookingId = $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertOk()
        ->json('booking_id');
    $booking = Booking::findOrFail($bookingId);
    storeExistingBoardingPass($booking);
    DB::table('type_pay_options')->insert([
        'id' => 2,
        'name' => 'Pay Online',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('type_payments')->insert([
        'id' => 3,
        'name' => 'Credit Card',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $lookupId = DB::table('lookups')->insertGetId([
        'name' => 'VAT',
        'slug' => 'vat',
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('lookup_items')->insert([
        'name' => 'VAT 15%',
        'slug' => 'vat_rate',
        'rate' => 15,
        'multiplier' => 1.15,
        'effective_from' => '2020-01-01',
        'is_default' => true,
        'is_active' => true,
        'team_id' => $fixture['teamId'],
        'lookup_id' => $lookupId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('front.payment.simulated', [
        'type' => 'booking',
        'booking' => $booking->id,
    ]))->assertOk();

    $this->assertDatabaseHas('bookings', [
        'id' => $booking->id,
        'status_payment_id' => 2,
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'description' => 'Payment recorded (simulated)',
        'subject_id' => $booking->id,
    ]);
    $this->assertDatabaseHas('files', [
        'booking_id' => $booking->id,
        'type_file_id' => 4,
        'table_name' => 'invoices',
    ]);
    Mail::assertNothingSent();
});

it('sends the existing booking email with its documents only once when enabled', function () {
    Mail::fake();
    Storage::fake('public');
    $fixture = createV2CheckoutCompletionFixture();
    $booking = createPaidV2CheckoutBooking($this, $fixture);
    storeExistingBoardingPass($booking);
    $emailDispatch = Mockery::mock(EmailDispatchService::class);
    $emailDispatch->shouldReceive('canSendAutomatic')
        ->twice()
        ->with($fixture['teamId'], EmailDispatchService::SETTING_AUTO_PAYMENT)
        ->andReturnTrue();
    $job = new FulfillBookingPayment($booking->id);

    $job->handle($emailDispatch, app(RendererService::class));
    $job->handle($emailDispatch, app(RendererService::class));

    Mail::assertSent(BookingMail::class, 1);
    $this->assertDatabaseHas('emails', [
        'auditable_type' => 'App\\Models\\Invoice',
        'team_id' => $fixture['teamId'],
        'sent' => true,
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'description' => 'Email sent (booking payment)',
        'subject_id' => $booking->id,
    ]);
    expect(DB::table('files')->where('booking_id', $booking->id)->count())->toBe(2);
});

it('attaches an item-specific boarding pass for every linked booking activity', function () {
    Mail::fake();
    Storage::fake('public');
    $fixture = createV2CheckoutCompletionFixture();
    $booking = createPaidV2CheckoutBooking($this, $fixture);
    $firstItem = $booking->booking_items()->firstOrFail();
    $secondItem = $firstItem->replicate();
    $secondItem->save();
    storeExistingBoardingPass($booking, $firstItem);
    storeExistingBoardingPass($booking, $secondItem);

    $emailDispatch = Mockery::mock(EmailDispatchService::class);
    $emailDispatch->shouldReceive('canSendAutomatic')
        ->once()
        ->with($fixture['teamId'], EmailDispatchService::SETTING_AUTO_PAYMENT)
        ->andReturnTrue();

    (new FulfillBookingPayment($booking->id))->handle($emailDispatch, app(RendererService::class));

    Mail::assertSent(BookingMail::class, function (BookingMail $mail) {
        $mail->build();

        return count($mail->rawAttachments) === 3;
    });
    expect(DB::table('files')
        ->where('booking_id', $booking->id)
        ->where('type_file_id', 6)
        ->count())->toBe(2);
});

it('keeps the booking paid and the email retryable when automatic delivery fails', function () {
    Storage::fake('public');
    $fixture = createV2CheckoutCompletionFixture();
    $booking = createPaidV2CheckoutBooking($this, $fixture);
    storeExistingBoardingPass($booking);
    $emailDispatch = Mockery::mock(EmailDispatchService::class);
    $emailDispatch->shouldReceive('canSendAutomatic')->once()->andReturnTrue();
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP unavailable'));

    expect(fn () => (new FulfillBookingPayment($booking->id))
        ->handle($emailDispatch, app(RendererService::class)))
        ->toThrow(RuntimeException::class, 'SMTP unavailable');

    $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status_payment_id' => 2]);
    $this->assertDatabaseHas('emails', [
        'auditable_type' => 'App\\Models\\Invoice',
        'sent' => false,
    ]);
});

it('does not create business records when terms are not accepted', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $payload = v2CheckoutCompletionPayload($fixture);
    $payload['terms_accepted'] = false;

    $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            $payload
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('terms_accepted');

    $this->assertDatabaseCount('bookings', 0);
    $this->assertDatabaseCount('customers', 0);
});

it('rejects an expired hold without creating a provisional booking', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $fixture['hold']->update(['expires_at' => now()->subSecond()]);

    $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('hold_uuid');

    $this->assertDatabaseCount('bookings', 0);
});

it('rejects a changed terms snapshot without creating business records', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $payload = v2CheckoutCompletionPayload($fixture);
    $payload['terms_hash'] = hash('sha256', 'stale conditions');

    $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            $payload
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('terms_hash');

    $this->assertDatabaseCount('bookings', 0);
    $this->assertDatabaseCount('customers', 0);
});

it('revalidates capacity when another booking consumes the held spaces', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $bookingId = DB::table('bookings')->insertGetId([
        'lead_name' => 'Competing Guest',
        'type_booking_id' => 1,
        'status_payment_id' => 1,
        'tot_paid' => 0,
        'grand_tot_due' => 7160,
        'reference' => 'REF-COMPETE',
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $bookingItemId = DB::table('booking_items')->insertGetId([
        'booking_id' => $bookingId,
        'service_id' => $fixture['serviceId'],
        'season_id' => $fixture['seasonId'],
        'inventory_id' => $fixture['inventoryId'],
        'pax_adults' => 8,
        'pax_children' => 0,
        'pax_guides' => 0,
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
    $inventoryTypeDayId = DB::table('inventory_type_days')
        ->where('inventory_id', $fixture['inventoryId'])
        ->where('type_day_id', CarbonImmutable::parse('2032-04-10')->dayOfWeekIso)
        ->value('id');
    DB::table('booking_inventories')->insert([
        'booking_item_id' => $bookingItemId,
        'booking_detail_id' => $bookingDetailId,
        'date' => '2032-04-10',
        'adults' => 8,
        'children' => 0,
        'guides' => 0,
        'pax' => 8,
        'inventory_type_day_id' => $inventoryTypeDayId,
        'inventory_time_slot_id' => $fixture['slotId'],
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slot_id');

    $this->assertDatabaseCount('bookings', 1);
    $this->assertDatabaseCount('customers', 0);
});

it('applies an eligible public promo and stores the server-calculated total', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $fixture['hold']->update(['adults' => 0, 'children' => 1]);
    DB::table('promotions')->insert([
        'name' => 'Public Ten Percent',
        'promo_code' => 'PUBLIC10',
        'type' => 'percent',
        'value' => 10,
        'booking_start_date' => '2026-01-01',
        'booking_end_date' => '2035-12-31',
        'activity_start_date' => '2032-01-01',
        'activity_end_date' => '2032-12-31',
        'internal_use_only' => false,
        'service_id' => $fixture['serviceId'],
        'season_id' => $fixture['seasonId'],
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $this->postJson(route('front.v2.checkout.promo', [
        'team' => $fixture['teamId'],
        'service' => $fixture['serviceId'],
    ]), [
        'promo_code' => 'PUBLIC10',
        'date' => '2032-04-10',
        'slot_id' => $fixture['slotId'],
        'season_id' => $fixture['seasonId'],
        'adults' => 0,
        'children' => 1,
    ])->assertOk()->assertJsonPath('pricing.total_price', 715.5);

    $payload = v2CheckoutCompletionPayload($fixture);
    $payload['promo_code'] = 'PUBLIC10';

    $bookingId = $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            $payload
        )
        ->assertOk()
        ->json('booking_id');

    $booking = Booking::with('booking_items.booking_fee')->findOrFail($bookingId);
    expect((float) $booking->booking_items->first()->booking_fee->tot_amount)->toBe(715.5)
        ->and((float) $booking->grand_tot_due)->toBe(770.5);
});

it('returns the same booking when checkout confirmation is safely retried', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $route = route('front.v2.checkout.confirm', [
        'team' => $fixture['teamId'],
        'service' => $fixture['serviceId'],
    ]);

    $this->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']]);
    $firstBookingId = $this->postJson($route, v2CheckoutCompletionPayload($fixture))
        ->assertOk()
        ->json('booking_id');
    $secondBookingId = $this->postJson($route, v2CheckoutCompletionPayload($fixture))
        ->assertOk()
        ->json('booking_id');

    expect($secondBookingId)->toBe($firstBookingId);
    $this->assertDatabaseCount('bookings', 1);
    $this->assertDatabaseCount('booking_terms_acceptances', 1);
});

it('completes a late PayFast payment once, allocates V2 extras, and alerts staff for review', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $bookingId = $this
        ->withSession(['public_booking_v2_session_key' => $fixture['holdSessionKey']])
        ->postJson(
            route('front.v2.checkout.confirm', [
                'team' => $fixture['teamId'],
                'service' => $fixture['serviceId'],
            ]),
            v2CheckoutCompletionPayload($fixture)
        )
        ->assertOk()
        ->json('booking_id');

    DB::table('type_pay_options')->insert([
        'id' => 2,
        'name' => 'Pay Online',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('type_payments')->insert([
        'id' => 3,
        'name' => 'Credit Card',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('type_alerts')->insert([
        'id' => 3,
        'name' => 'Booking confirmation',
        'slug' => 'booking_confirmation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $lookupId = DB::table('lookups')->insertGetId([
        'name' => 'VAT',
        'slug' => 'vat',
        'team_id' => $fixture['teamId'],
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('lookup_items')->insert([
        'name' => 'VAT 15%',
        'slug' => 'vat_rate',
        'rate' => 15,
        'multiplier' => 1.15,
        'effective_from' => '2020-01-01',
        'is_default' => true,
        'is_active' => true,
        'team_id' => $fixture['teamId'],
        'lookup_id' => $lookupId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $legacyExtraId = DB::table('booking_extras')->insertGetId([
        'booking_id' => $bookingId,
        'team_id' => $fixture['teamId'],
        'type' => 'custom',
        'name' => 'Staff-added extra',
        'quantity' => 1,
        'unit_price' => 100,
        'total_amount' => 100,
        'exclude_from_franchise_fees' => true,
        'meta' => json_encode(['source' => 'admin_booking']),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $booking = Booking::findOrFail($bookingId);
    $booking->update(['payment_window_expired_at' => now()->subMinute()]);
    $method = new ReflectionMethod(PaymentController::class, 'completeBookingPaymentDbOnly');
    $method->setAccessible(true);
    $method->invoke(app(PaymentController::class), $booking, null, ['event' => 'test-late-payment']);
    $method->invoke(app(PaymentController::class), $booking, null, ['event' => 'test-duplicate-notification']);

    $this->assertDatabaseHas('bookings', [
        'id' => $bookingId,
        'status_payment_id' => 2,
        'tot_paid' => 2750.00,
    ]);
    $this->assertDatabaseCount('booking_payments', 1);
    $this->assertDatabaseHas('booking_extra_payment_allocations', [
        'booking_id' => $bookingId,
        'amount' => 165.00,
        'allocation_type' => 'payment',
    ]);
    $this->assertDatabaseCount('booking_extra_payment_allocations', 1);
    $this->assertDatabaseMissing('booking_extra_payment_allocations', [
        'booking_extra_id' => $legacyExtraId,
    ]);
    $this->assertDatabaseHas('alerts', [
        'booking_id' => $bookingId,
        'alert_text' => 'Late online payment requires capacity review',
    ]);
    $this->assertDatabaseCount('alerts', 1);
});

it('keeps expired unpaid provisional bookings out of operational queries while retaining paid late bookings', function () {
    $fixture = createV2CheckoutCompletionFixture();
    $createBooking = function (string $reference, int $status, ?string $expiredAt) use ($fixture): int {
        $bookingId = DB::table('bookings')->insertGetId([
            'lead_name' => $reference,
            'type_booking_id' => 1,
            'status_payment_id' => $status,
            'payment_window_expired_at' => $expiredAt,
            'tot_paid' => $status === 2 ? 895 : 0,
            'grand_tot_due' => 895,
            'reference' => $reference,
            'team_id' => $fixture['teamId'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('booking_items')->insert([
            'booking_id' => $bookingId,
            'service_id' => $fixture['serviceId'],
            'season_id' => $fixture['seasonId'],
            'inventory_id' => $fixture['inventoryId'],
            'pax_adults' => 1,
            'team_id' => $fixture['teamId'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $bookingId;
    };

    $activeId = $createBooking('REF-ACTIVE', 1, null);
    $expiredId = $createBooking('REF-EXPIRED', 1, now()->subHour()->format('Y-m-d H:i:s'));
    $paidLateId = $createBooking('REF-PAID-LATE', 2, now()->subHour()->format('Y-m-d H:i:s'));

    $operationalIds = Booking::query()->operational()->pluck('id');
    $reportedBookingIds = (new BookingsReportExport($fixture['teamId'], null, null, null))
        ->query()
        ->pluck('booking_id');

    expect($operationalIds)->toContain($activeId, $paidLateId)
        ->and($operationalIds)->not->toContain($expiredId)
        ->and($reportedBookingIds)->toContain($activeId, $paidLateId)
        ->and($reportedBookingIds)->not->toContain($expiredId);
});

