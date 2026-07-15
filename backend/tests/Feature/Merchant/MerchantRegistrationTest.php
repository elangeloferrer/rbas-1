<?php

use App\Mail\Merchant\VerifyEmailMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Tests\Helpers\MerchantTestHelper;

uses(MerchantTestHelper::class);

beforeEach(function () {
    $this->seedRoles();
    Mail::fake();
});

// ── Happy path ─────────────────────────────────────────────────────────────

it('registers a merchant and returns 201 with user data', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload())
        ->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.role', 'merchant')
        ->assertJsonPath('data.user.is_email_verified', false);
});

it('sends exactly one verification email on registration', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload());

    Mail::assertQueued(VerifyEmailMail::class, 1);
});

it('assigns the merchant role to the new user', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload());

    $user = User::where('email', 'jane@merchant.test')->firstOrFail();

    expect($user->hasRole('merchant'))->toBeTrue();
});

it('stores a hashed password — not the plain text', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload());

    $user = User::where('email', 'jane@merchant.test')->firstOrFail();

    expect($user->password)
        ->not->toBe($this->password)
        ->toStartWith('$2y$');
});

it('logs the merchant in immediately after registration (soft gate)', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload())
        ->assertStatus(201);

    $this->getJson('/api/user')->assertOk();
});

// ── Validation ─────────────────────────────────────────────────────────────

it('rejects a duplicate email with 422', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload());

    $this->postJson('/api/merchant/register', $this->registrationPayload())
        ->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('rejects a weak password with 422', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload([
        'password' => 'weakpassword',
        'password_confirmation' => 'weakpassword',
    ]))->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('rejects mismatched password confirmation with 422', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload([
        'password_confirmation' => 'Different1!',
    ]))->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('requires all fields — returns 422 on empty body', function () {
    $this->postJson('/api/merchant/register', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['first_name', 'email', 'password']);
});

it('rejects an invalid email format with 422', function () {
    $this->postJson('/api/merchant/register', $this->registrationPayload([
        'email' => 'not-an-email',
    ]))->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
