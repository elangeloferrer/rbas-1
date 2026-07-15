<?php

use App\Mail\Merchant\VerifyEmailMail;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\MerchantTestHelper;

uses(MerchantTestHelper::class);

beforeEach(function () {
    $this->seedRoles();
    Mail::fake();
});

// ── Verify endpoint ────────────────────────────────────────────────────────

it('verifies email with valid signed params', function () {
    $merchant = $this->createUnverifiedMerchant();
    $params = $this->signedVerifyParams($merchant);

    $this->postJson('/api/merchant/email/verify', $params)
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($merchant->fresh()->email_verified_at)->not->toBeNull();
});

it('is idempotent — re-verifying an already-verified email returns 200', function () {
    $merchant = $this->createMerchant(); // already verified
    $params = $this->signedVerifyParams($merchant);

    $this->postJson('/api/merchant/email/verify', $params)
        ->assertOk();
});

it('rejects a tampered signature with 403', function () {
    $merchant = $this->createUnverifiedMerchant();
    $params = $this->signedVerifyParams($merchant);
    $params['signature'] = 'tampered-invalid-signature';

    $this->postJson('/api/merchant/email/verify', $params)
        ->assertForbidden();
});

it('rejects an expired link with 403', function () {
    $merchant = $this->createUnverifiedMerchant();

    // Travel forward past the expiry, then generate params that are already expired
    $this->travel(1441)->minutes();
    $params = $this->signedVerifyParams($merchant, expiresMinutes: -1);

    $this->postJson('/api/merchant/email/verify', $params)
        ->assertForbidden();
});

it('rejects a wrong hash with 403', function () {
    $merchant = $this->createUnverifiedMerchant();
    $params = $this->signedVerifyParams($merchant);
    $params['hash'] = sha1('wrong@email.test');

    // Signature no longer matches after altering hash — expect 403
    $this->postJson('/api/merchant/email/verify', $params)
        ->assertForbidden();
});

it('requires all verification fields — returns 422 on empty body', function () {
    $this->postJson('/api/merchant/email/verify', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['id', 'hash', 'expires', 'signature']);
});

// ── Resend endpoint ────────────────────────────────────────────────────────

it('resends verification email to an authenticated unverified merchant', function () {
    $merchant = $this->createUnverifiedMerchant();

    $this->actingAsMerchant($merchant)
        ->postJson('/api/merchant/email/resend')
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertQueued(VerifyEmailMail::class, 1);
});

it('returns 401 on resend when not authenticated', function () {
    $this->postJson('/api/merchant/email/resend')
        ->assertUnauthorized();
});

it('does not send an email on resend if already verified', function () {
    $merchant = $this->createMerchant(); // already verified

    $this->actingAsMerchant($merchant)
        ->postJson('/api/merchant/email/resend')
        ->assertOk();

    Mail::assertNotQueued(VerifyEmailMail::class);
});
