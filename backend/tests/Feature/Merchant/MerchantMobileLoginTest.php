<?php

use Tests\Helpers\MerchantTestHelper;

uses(MerchantTestHelper::class);

beforeEach(fn () => $this->seedRoles());

// ── Happy path ─────────────────────────────────────────────────────────────

it('returns a Bearer token on successful mobile login', function () {
    $merchant = $this->createMerchant();

    $this->postJson('/api/mobile/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
        'device_name' => 'iPhone 15',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['user', 'token']]);
});

it('token in the response is a valid plain-text Sanctum token', function () {
    $merchant = $this->createMerchant();

    $token = $this->postJson('/api/mobile/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
        'device_name' => 'iPhone 15',
    ])->assertOk()->json('data.token');

    expect($token)->toBeString()->not->toBeEmpty();
    // Sanctum plain tokens contain a '|' separator
    expect($token)->toContain('|');
});

it('revokes the previous token for the same device on re-login', function () {
    $merchant = $this->createMerchant();

    $payload = [
        'email' => $merchant->email,
        'password' => $this->password,
        'device_name' => 'iPhone 15',
    ];

    $this->postJson('/api/mobile/merchant/login', $payload)->assertOk();
    expect($merchant->fresh()->tokens()->count())->toBe(1);

    // Second login with same device — old token revoked, new one issued
    $this->postJson('/api/mobile/merchant/login', $payload)->assertOk();
    expect($merchant->fresh()->tokens()->count())->toBe(1);
});

it('issues separate tokens for different device names', function () {
    $merchant = $this->createMerchant();

    $this->postJson('/api/mobile/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
        'device_name' => 'iPhone 15',
    ])->assertOk();

    $this->postJson('/api/mobile/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
        'device_name' => 'iPad Pro',
    ])->assertOk();

    expect($merchant->fresh()->tokens()->count())->toBe(2);
});

// ── Failure cases ───────────────────────────────────────────────────────────

it('rejects wrong password on mobile login with 401', function () {
    $merchant = $this->createMerchant();

    $this->postJson('/api/mobile/merchant/login', [
        'email' => $merchant->email,
        'password' => 'WrongPassword1!',
        'device_name' => 'iPhone 15',
    ])->assertStatus(401);
});

it('allows an unverified merchant on mobile login (soft gate — email check is deferred)', function () {
    $merchant = $this->createUnverifiedMerchant();

    $this->postJson('/api/mobile/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
        'device_name' => 'iPhone 15',
    ])->assertOk();
});

it('blocks an inactive merchant on mobile login with 403', function () {
    $merchant = $this->createInactiveMerchant();

    $this->postJson('/api/mobile/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
        'device_name' => 'iPhone 15',
    ])->assertStatus(403);
});

it('succeeds without device_name — defaults to the "mobile" label', function () {
    $merchant = $this->createMerchant();

    $this->postJson('/api/mobile/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password,
    ])->assertOk()
        ->assertJsonStructure(['data' => ['user', 'token']]);
});
