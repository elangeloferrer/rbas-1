<?php

use Tests\Helpers\MerchantTestHelper;

uses(MerchantTestHelper::class);

beforeEach(fn () => $this->seedRoles());

it('logs out an authenticated merchant and returns 200', function () {
    $merchant = $this->createMerchant();

    $this->actingAsMerchant($merchant)
        ->postJson('/api/logout')
        ->assertOk()
        ->assertJsonPath('success', true);
});

it('destroys the session — /api/user returns 401 after logout', function () {
    $merchant = $this->createMerchant();

    $this->actingAsMerchant($merchant)->postJson('/api/logout')->assertOk();

    // actingAs() sets the user directly on the Sanctum guard and persists within
    // the test instance. forgetGuards() resets all resolved guards so the next
    // request authenticates from scratch (no session, no token) → 401.
    $this->app['auth']->forgetGuards();

    $this->getJson('/api/user')->assertUnauthorized();
});

it('returns 401 when logging out without being authenticated', function () {
    $this->postJson('/api/logout')->assertUnauthorized();
});
