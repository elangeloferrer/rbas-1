<?php

use Tests\Helpers\MerchantTestHelper;

uses(MerchantTestHelper::class);

beforeEach(fn () => $this->seedRoles());

// ── Merchant dashboard access ──────────────────────────────────────────────

it('allows a verified active merchant to access the merchant dashboard', function () {
    $merchant = $this->createMerchant();

    $this->actingAsMerchant($merchant)
        ->getJson('/api/merchant/dashboard')
        ->assertOk();
});

it('blocks an unauthenticated user from the merchant dashboard with 401', function () {
    $this->getJson('/api/merchant/dashboard')->assertUnauthorized();
});

it('blocks an inactive merchant from the merchant dashboard with 403', function () {
    $merchant = $this->createInactiveMerchant();

    $this->actingAsMerchant($merchant)
        ->getJson('/api/merchant/dashboard')
        ->assertForbidden();
});

it('blocks a customer from accessing the merchant dashboard with 403', function () {
    $this->actingAs($this->createCustomer(), 'sanctum')
        ->getJson('/api/merchant/dashboard')
        ->assertForbidden();
});

it('blocks an admin from accessing the merchant dashboard with 403', function () {
    $this->actingAs($this->createAdmin(), 'sanctum')
        ->getJson('/api/merchant/dashboard')
        ->assertForbidden();
});

// ── /api/user (shared, any authenticated role) ─────────────────────────────

it('allows any authenticated active user to call /api/user', function () {
    $merchant = $this->createMerchant();

    $this->actingAsMerchant($merchant)
        ->getJson('/api/user')
        ->assertOk()
        ->assertJsonPath('data.role', 'merchant');
});

it('blocks an inactive merchant from /api/user with 403', function () {
    $merchant = $this->createInactiveMerchant();

    $this->actingAsMerchant($merchant)
        ->getJson('/api/user')
        ->assertForbidden();
});
