<?php

use App\Http\Controllers\Front\PaymentController;
use App\Jobs\FulfillGiftVoucherPayment;
use App\Mail\MessageGuestMail;
use App\Models\GiftVoucher;
use App\Services\EmailDispatchService;
use App\Services\PurchaseAnalyticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

function createGiftVoucherPurchaseFixture(): array
{
    DB::table('status_payments')->insert([
        [
            'id' => 1,
            'name' => 'Pending',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 2,
            'name' => 'Paid',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    $teamId = DB::table('teams')->insertGetId([
        'name' => 'Gift Voucher Test Site',
        'is_published' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $serviceId = DB::table('services')->insertGetId([
        'name' => 'Gift Voucher Test Tour',
        'capacity' => 8,
        'is_online_bookable' => true,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $seasonId = DB::table('seasons')->insertGetId([
        'name' => 'Gift Voucher Test Season',
        'start_date' => '2026-01-01',
        'end_date' => '2026-12-31',
        'rake_rate_adult' => 895,
        'rake_rate_child' => 795,
        'rake_rate_guide' => 0,
        'status' => true,
        'internal_only' => false,
        'service_id' => $serviceId,
        'team_id' => $teamId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('teamId', 'serviceId', 'seasonId');
}

function giftVoucherPurchasePayload(int $seasonId): array
{
    return [
        'full_name' => 'Voucher Buyer',
        'email' => 'buyer@example.com',
        'phone' => '+27821234567',
        'adults' => 2,
        'children' => 1,
        'activity_id' => $seasonId,
    ];
}

it('prices a gift voucher from the adult and child season rates', function () {
    $fixture = createGiftVoucherPurchaseFixture();

    $response = $this->postJson(
        route('front.booking.gift-store'),
        giftVoucherPurchasePayload($fixture['seasonId'])
    );

    $response->assertOk()->assertJson(['success' => true]);
    $voucherId = $response->json('voucher_id');

    $this->assertDatabaseHas('gift_vouchers', [
        'id' => $voucherId,
        'grand_tot_due' => 2585.00,
        'status_payment_id' => 1,
        'redeemed' => false,
    ]);
    $this->assertDatabaseHas('gift_voucher_items', [
        'gift_voucher_id' => $voucherId,
        'pax_adults' => 2,
        'pax_children' => 1,
        'price_adult' => 1790.00,
        'price_child' => 795.00,
        'team_id' => $fixture['teamId'],
        'service_id' => $fixture['serviceId'],
        'season_id' => $fixture['seasonId'],
    ]);
});

it('allows a child-only gift voucher', function () {
    $fixture = createGiftVoucherPurchaseFixture();
    $payload = giftVoucherPurchasePayload($fixture['seasonId']);
    $payload['adults'] = 0;
    $payload['children'] = 1;

    $response = $this->postJson(route('front.booking.gift-store'), $payload)
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('gift_vouchers', [
        'id' => $response->json('voucher_id'),
        'grand_tot_due' => 795.00,
    ]);
    $this->assertDatabaseHas('gift_voucher_items', [
        'gift_voucher_id' => $response->json('voucher_id'),
        'pax_adults' => 0,
        'pax_children' => 1,
        'price_adult' => 0.00,
        'price_child' => 795.00,
    ]);
});

it('allows an adult-only gift voucher', function () {
    $fixture = createGiftVoucherPurchaseFixture();
    $payload = giftVoucherPurchasePayload($fixture['seasonId']);
    $payload['adults'] = 1;
    $payload['children'] = 0;

    $response = $this->postJson(route('front.booking.gift-store'), $payload)
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('gift_vouchers', [
        'id' => $response->json('voucher_id'),
        'grand_tot_due' => 895.00,
    ]);
    $this->assertDatabaseHas('gift_voucher_items', [
        'gift_voucher_id' => $response->json('voucher_id'),
        'pax_adults' => 1,
        'pax_children' => 0,
        'price_adult' => 895.00,
        'price_child' => 0.00,
    ]);
});

it('rejects a gift voucher without any guests before storing records', function () {
    $fixture = createGiftVoucherPurchaseFixture();
    $payload = giftVoucherPurchasePayload($fixture['seasonId']);
    $payload['adults'] = 0;
    $payload['children'] = 0;

    $this->postJson(route('front.booking.gift-store'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('adults');

    $this->assertDatabaseCount('gift_vouchers', 0);
    $this->assertDatabaseCount('gift_voucher_items', 0);
});

it('creates a new voucher for each checkout in the same browser session', function () {
    $fixture = createGiftVoucherPurchaseFixture();
    $payload = giftVoucherPurchasePayload($fixture['seasonId']);

    $firstVoucherId = $this->postJson(route('front.booking.gift-store'), $payload)
        ->assertOk()
        ->json('voucher_id');
    $secondVoucherId = $this->postJson(route('front.booking.gift-store'), $payload)
        ->assertOk()
        ->json('voucher_id');

    expect($secondVoucherId)->not->toBe($firstVoucherId);
    $this->assertDatabaseCount('gift_vouchers', 2);
    $this->assertDatabaseCount('gift_voucher_items', 2);
    $this->assertDatabaseHas('gift_voucher_items', ['gift_voucher_id' => $firstVoucherId]);
    $this->assertDatabaseHas('gift_voucher_items', ['gift_voucher_id' => $secondVoucherId]);
});

it('provides the voucher item team to the gift payment page', function () {
    $fixture = createGiftVoucherPurchaseFixture();
    $this->startSession();
    $voucherId = $this->postJson(
        route('front.booking.gift-store'),
        giftVoucherPurchasePayload($fixture['seasonId'])
    )->assertOk()->json('voucher_id');
    $voucher = GiftVoucher::findOrFail($voucherId);

    expect(session()->getId())->toBe($voucher->session_id);

    // The array session driver rotates IDs between separate test requests, so render
    // through the controller while retaining the purchase request's active session.
    $response = app(PaymentController::class)->gift($voucher)->toResponse(request());

    TestResponse::fromBaseResponse($response)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('GiftPayment')
            ->where('gift.id', $voucherId)
            ->where('gift.team_id', $fixture['teamId']));
});

it('marks a paid gift voucher as available rather than redeemed', function () {
    Mail::fake();
    Queue::fake();
    $fixture = createGiftVoucherPurchaseFixture();
    $voucherId = $this->postJson(
        route('front.booking.gift-store'),
        giftVoucherPurchasePayload($fixture['seasonId'])
    )->assertOk()->json('voucher_id');
    $voucher = GiftVoucher::findOrFail($voucherId);

    $method = new ReflectionMethod(PaymentController::class, 'recordVoucherPayment');
    $method->setAccessible(true);
    $method->invoke(app(PaymentController::class), $voucher, null, ['event' => 'test']);
    $method->invoke(app(PaymentController::class), $voucher, null, ['event' => 'test-retry']);

    $this->assertDatabaseHas('gift_vouchers', [
        'id' => $voucherId,
        'status_payment_id' => 2,
        'redeemed' => false,
        'redeemed_date' => null,
    ]);
    $this->assertDatabaseHas('audit_logs', [
        'description' => 'Gift voucher payment recorded',
        'subject_id' => $voucherId,
        'subject_type' => GiftVoucher::class,
    ]);
    expect(DB::table('audit_logs')
        ->where('description', 'Gift voucher payment recorded')
        ->where('subject_id', $voucherId)
        ->count())->toBe(1);
    $this->assertDatabaseCount('emails', 0);
    Mail::assertNothingSent();
});

it('exposes a paid gift voucher as a site-affiliated purchase', function () {
    $fixture = createGiftVoucherPurchaseFixture();
    $voucherId = $this->postJson(
        route('front.booking.gift-store'),
        giftVoucherPurchasePayload($fixture['seasonId'])
    )->assertOk()->json('voucher_id');
    GiftVoucher::whereKey($voucherId)->update([
        'status_payment_id' => 2,
        'paid_date' => now(),
    ]);
    $voucher = GiftVoucher::findOrFail($voucherId);
    $purchaseContext = app(PurchaseAnalyticsService::class)
        ->issueContextToken('voucher', $voucherId);

    $response = $this->getJson(route('front.payment.payfast.purchase-status', [
        'purchase_context' => $purchaseContext,
    ]))->assertOk();

    $response
        ->assertJsonPath('state', 'paid')
        ->assertJsonPath('purchase.event', 'purchase')
        ->assertJsonPath('purchase.ecommerce.transaction_id', 'gift-voucher-' . $voucher->voucher_code)
        ->assertJsonPath('purchase.ecommerce.value', 2585)
        ->assertJsonPath('purchase.ecommerce.currency', 'ZAR')
        ->assertJsonPath('purchase.ecommerce.items.0.item_name', 'Gift Voucher Test Tour Gift Voucher')
        ->assertJsonPath('purchase.ecommerce.items.0.affiliation', 'Gift Voucher Test Site')
        ->assertJsonPath('purchase.ecommerce.items.0.price', 2585);

    expect($response->getContent())
        ->not->toContain('buyer@example.com')
        ->not->toContain('+27821234567')
        ->not->toContain('Voucher Buyer');
});

it('prepares the voucher PDF after payment and sends a verification-safe email only once', function () {
    Mail::fake();
    Storage::fake('public');
    $fixture = createGiftVoucherPurchaseFixture();
    $voucherId = $this->postJson(
        route('front.booking.gift-store'),
        giftVoucherPurchasePayload($fixture['seasonId'])
    )->assertOk()->json('voucher_id');
    GiftVoucher::whereKey($voucherId)->update([
        'status_payment_id' => 2,
        'paid_date' => now(),
        'redeemed' => false,
    ]);

    $emailDisabled = Mockery::mock(EmailDispatchService::class);
    $emailDisabled->shouldReceive('canSendAutomatic')->once()->andReturnFalse();
    $job = new FulfillGiftVoucherPayment($voucherId);
    $job->handle($emailDisabled);

    Storage::disk('public')->assertExists('gift_vouchers/' . GiftVoucher::findOrFail($voucherId)->voucher_code . '.pdf');
    $email = DB::table('emails')->where('auditable_id', $voucherId)->first();
    expect($email)->not->toBeNull()
        ->and((bool) $email->sent)->toBeFalse()
        ->and($email->body)->toContain('received the payment notification')
        ->and($email->body)->toContain('Payment will be verified')
        ->and($email->body)->not->toContain('has been paid');
    Mail::assertNothingSent();

    $emailEnabled = Mockery::mock(EmailDispatchService::class);
    $emailEnabled->shouldReceive('canSendAutomatic')->once()->andReturnTrue();
    $job->handle($emailEnabled);
    $job->handle($emailEnabled);

    Mail::assertSent(MessageGuestMail::class, 1);
    $this->assertDatabaseHas('emails', [
        'auditable_type' => GiftVoucher::class,
        'auditable_id' => $voucherId,
        'team_id' => $fixture['teamId'],
        'sent' => true,
    ]);
});

it('keeps the voucher paid and retryable when email delivery fails', function () {
    Storage::fake('public');
    $fixture = createGiftVoucherPurchaseFixture();
    $voucherId = $this->postJson(
        route('front.booking.gift-store'),
        giftVoucherPurchasePayload($fixture['seasonId'])
    )->assertOk()->json('voucher_id');
    GiftVoucher::whereKey($voucherId)->update([
        'status_payment_id' => 2,
        'paid_date' => now(),
        'redeemed' => false,
    ]);

    $emailEnabled = Mockery::mock(EmailDispatchService::class);
    $emailEnabled->shouldReceive('canSendAutomatic')->once()->andReturnTrue();
    Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('SMTP unavailable'));

    expect(fn () => (new FulfillGiftVoucherPayment($voucherId))->handle($emailEnabled))
        ->toThrow(RuntimeException::class, 'SMTP unavailable');

    $this->assertDatabaseHas('gift_vouchers', [
        'id' => $voucherId,
        'status_payment_id' => 2,
        'redeemed' => false,
    ]);
    $this->assertDatabaseHas('emails', [
        'auditable_type' => GiftVoucher::class,
        'auditable_id' => $voucherId,
        'sent' => false,
    ]);
});

it('uses the deferred fulfilment path for a simulated voucher payment', function () {
    Mail::fake();
    Storage::fake('public');
    $fixture = createGiftVoucherPurchaseFixture();
    $voucherId = $this->postJson(
        route('front.booking.gift-store'),
        giftVoucherPurchasePayload($fixture['seasonId'])
    )->assertOk()->json('voucher_id');

    $this->get(route('front.payment.simulated', [
        'type' => 'voucher',
        'gift_id' => $voucherId,
    ]))->assertOk();

    $voucher = GiftVoucher::findOrFail($voucherId);
    $this->assertDatabaseHas('gift_vouchers', [
        'id' => $voucherId,
        'status_payment_id' => 2,
        'redeemed' => false,
    ]);
    Storage::disk('public')->assertExists('gift_vouchers/' . $voucher->voucher_code . '.pdf');
    $this->assertDatabaseHas('emails', [
        'auditable_type' => GiftVoucher::class,
        'auditable_id' => $voucherId,
        'attachment_path' => 'gift_vouchers/' . $voucher->voucher_code . '.pdf',
        'sent' => false,
    ]);
});
