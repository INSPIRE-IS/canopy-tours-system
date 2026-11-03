<?php

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Route::post('/admin/test-expired-session', function () {
        throw new TokenMismatchException;
    })->middleware('web');

    Route::post('/test-public-expired-session', function () {
        throw new TokenMismatchException;
    })->middleware('web');
});

test('expired admin form submissions redirect to login and preserve the admin return page', function () {
    $returnUrl = url('/admin/promotions');

    $response = $this
        ->withHeader('Referer', $returnUrl)
        ->post('/admin/test-expired-session');

    $response->assertRedirect(route('login'));
    expect(session('url.intended'))->toBe($returnUrl)
        ->and(session('session_expired'))->toBe('Your session expired. Please sign in again to continue.');
});

test('expired Inertia submissions issue a client-side login location response', function () {
    $response = $this
        ->withHeaders([
            'Referer' => url('/admin/promotions'),
            'X-Inertia' => 'true',
        ])
        ->post('/admin/test-expired-session');

    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', route('login'));
});

test('expired admin submissions do not retain an external return URL', function () {
    $this
        ->withHeader('Referer', 'https://example.com/admin/promotions')
        ->post('/admin/test-expired-session')
        ->assertRedirect(route('login'));

    expect(session('url.intended'))->toBe(route('admin.dashboard'));
});

test('expired JSON submissions provide the login redirect for the Axios interceptor', function () {
    $response = $this
        ->withHeader('Referer', url('/admin/promotions'))
        ->postJson('/admin/test-expired-session');

    $response->assertStatus(419)
        ->assertJson([
            'message' => 'Your session expired. Please sign in again to continue.',
            'redirect' => route('login'),
        ]);
});

test('login renders the expired-session message', function () {
    $this
        ->withSession(['session_expired' => 'Your session expired. Please sign in again to continue.'])
        ->get('/login')
        ->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('sessionExpiredMessage', 'Your session expired. Please sign in again to continue.')
        );
});

test('public token mismatches keep the existing response behavior', function () {
    $this
        ->post('/test-public-expired-session')
        ->assertStatus(419);
});
