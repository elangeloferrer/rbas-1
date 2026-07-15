<?php

use Tests\Helpers\MerchantTestHelper;

uses(MerchantTestHelper::class);

beforeEach(fn () => $this->seedRoles());

// ── Happy path ─────────────────────────────────────────────────────────────

it('logs in a verified merchant and returns 200 with user data', function () {
    $merchant = $this->createMerchant();

    $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', $merchant->email)
        ->assertJsonPath('data.user.role', 'merchant')
        ->assertJsonPath('data.user.is_email_verified', true);
});

it('does not expose a token in the SPA login response', function () {
    $merchant = $this->createMerchant();

    $response = $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
    ])->assertOk()->json();

    expect($response)->not->toHaveKey('token');
    expect($response['data'] ?? [])->not->toHaveKey('token');
});

it('creates an authenticated session after login', function () {
    $merchant = $this->createMerchant();

    $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
    ])->assertOk();

    $this->getJson('/api/user')->assertOk();
});

// ── Failure cases ───────────────────────────────────────────────────────────

it('rejects wrong password with 401', function () {
    $merchant = $this->createMerchant();

    $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => 'WrongPassword1!',
    ])->assertStatus(401);
});

it('rejects a non-existent email with 401', function () {
    $this->postJson('/api/merchant/login', [
        'email' => 'ghost@nowhere.test',
        'password' => $this->password,
    ])->assertStatus(401);
});

it('allows an unverified merchant to log in (soft gate — email check is deferred)', function () {
    $merchant = $this->createUnverifiedMerchant();

    $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
    ])->assertOk()
        ->assertJsonPath('data.user.is_email_verified', false);
});

it('blocks an inactive merchant with 403', function () {
    $merchant = $this->createInactiveMerchant();

    $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
    ])->assertStatus(403);
});

it('rejects a customer trying to log in via the merchant endpoint with 401', function () {
    $customer = $this->createCustomer();

    $this->postJson('/api/merchant/login', [
        'email' => $customer->email,
        'password' => $this->password,
    ])->assertStatus(401);
});

// ── Rate limiting ──────────────────────────────────────────────────────────

it('returns 429 after exceeding 5 login attempts per minute', function () {
    $merchant = $this->createMerchant();

    foreach (range(1, 5) as $_) {
        $this->postJson('/api/merchant/login', [
            'email' => $merchant->email,
            'password' => 'WrongPassword1!',
        ]);
    }

    $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => 'WrongPassword1!',
    ])->assertStatus(429)
        ->assertJsonPath('retry_after', fn ($v) => is_int($v) && $v > 0);
});
