<?php

use App\Mail\Merchant\PasswordResetMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\MerchantTestHelper;

uses(MerchantTestHelper::class);

beforeEach(function () {
    $this->seedRoles();
    Mail::fake();
});

// ── Forgot password ────────────────────────────────────────────────────────

it('queues a reset email for a valid merchant email', function () {
    $merchant = $this->createMerchant();

    $this->postJson('/api/merchant/forgot-password', ['email' => $merchant->email])
        ->assertOk()
        ->assertJsonPath('success', true);

    Mail::assertQueued(PasswordResetMail::class, 1);
});

it('returns 200 for a non-existent email — no enumeration', function () {
    $this->postJson('/api/merchant/forgot-password', ['email' => 'ghost@nowhere.test'])
        ->assertOk();

    Mail::assertNotQueued(PasswordResetMail::class);
});

it('returns 200 for a customer email on the merchant endpoint — no enumeration', function () {
    $customer = $this->createCustomer();

    $this->postJson('/api/merchant/forgot-password', ['email' => $customer->email])
        ->assertOk();

    Mail::assertNotQueued(PasswordResetMail::class);
});

it('requires a valid email format for forgot-password', function () {
    $this->postJson('/api/merchant/forgot-password', ['email' => 'not-an-email'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

// ── Reset password ─────────────────────────────────────────────────────────

it('resets the password with a valid token', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant);
    $newPassword = 'NewSecret2@';

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertOk()
        ->assertJsonPath('success', true);
});

it('can log in with the new password after reset', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant);
    $newPassword = 'NewSecret2@';

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertOk();

    $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => $newPassword,
    ])->assertOk();
});

it('cannot log in with the old password after reset', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant);
    $newPassword = 'NewSecret2@';

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertOk();

    $this->postJson('/api/merchant/login', [
        'email' => $merchant->email,
        'password' => $this->password, // the OLD password
    ])->assertStatus(401);
});

it('deletes the token after a successful reset', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant);
    $newPassword = 'NewSecret2@';

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertOk();

    expect(
        DB::table('password_reset_tokens')->where('email', $merchant->email)->exists()
    )->toBeFalse();
});

it('revokes all Sanctum tokens after password reset', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant);

    $merchant->createToken('mobile-device');
    expect($merchant->tokens()->count())->toBe(1);

    $newPassword = 'NewSecret2@';

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertOk();

    expect($merchant->fresh()->tokens()->count())->toBe(0);
});

it('cannot reuse the token after a successful reset', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant);
    $newPassword = 'NewSecret2@';

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => $newPassword,
        'password_confirmation' => $newPassword,
    ])->assertOk();

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => 'AnotherNew3!',
        'password_confirmation' => 'AnotherNew3!',
    ])->assertStatus(422);
});

it('rejects an invalid token with 422', function () {
    $merchant = $this->createMerchant();
    $this->createResetToken($merchant); // token exists but we send the wrong one

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => 'completely-invalid-token',
        'password' => 'NewSecret2@',
        'password_confirmation' => 'NewSecret2@',
    ])->assertStatus(422);
});

it('rejects an expired token with 422', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant, ageMinutes: 61);

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => 'NewSecret2@',
        'password_confirmation' => 'NewSecret2@',
    ])->assertStatus(422);
});

it('rejects a weak password on reset with 422', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant);

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => 'weakpassword',
        'password_confirmation' => 'weakpassword',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('rejects mismatched password confirmation on reset with 422', function () {
    $merchant = $this->createMerchant();
    $plainToken = $this->createResetToken($merchant);

    $this->postJson('/api/merchant/reset-password', [
        'email' => $merchant->email,
        'token' => $plainToken,
        'password' => 'NewSecret2@',
        'password_confirmation' => 'DoesNotMatch2@',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('requires all fields for reset-password', function () {
    $this->postJson('/api/merchant/reset-password', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'token', 'password']);
});
