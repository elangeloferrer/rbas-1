<?php

use App\Models\User;
use App\Mail\Merchant\VerifyEmailMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

it('sends exactly one verification email on merchant registration', function () {
    $this->postJson('/api/merchant/register', [
        'first_name'            => 'Jane',
        'email'                 => 'jane@merchant.com',
        'password'              => 'Password1',
        'password_confirmation' => 'Password1',
    ])->assertStatus(201);

    Mail::assertQueuedCount(1);
    Mail::assertQueued(
        VerifyEmailMail::class,
        fn ($mail) => $mail->hasTo('jane@merchant.com')
    );
});

it('verifies a merchant email with valid signed params', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());

    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);

    $this->postJson('/api/merchant/email/verify', [
        'id'        => $user->id,
        'hash'      => sha1($user->email),
        'expires'   => $params['expires'],
        'signature' => $params['signature'],
    ])->assertOk()->assertJson(['success' => true]);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});

it('rejects a tampered signature', function () {
    $user = User::factory()->create();

    $this->postJson('/api/merchant/email/verify', [
        'id'        => $user->id,
        'hash'      => sha1($user->email),
        'expires'   => now()->addHour()->timestamp,
        'signature' => str_repeat('0', 64),
    ])->assertStatus(403)->assertJson(['success' => false]);
});

it('returns 403 for an expired verification link', function () {
    $user = User::factory()->create(['email_verified_at' => null]);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());

    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinute(), // already expired
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);

    $this->postJson('/api/merchant/email/verify', [
        'id'        => $user->id,
        'hash'      => sha1($user->email),
        'expires'   => $params['expires'],
        'signature' => $params['signature'],
    ])->assertStatus(403);
});

it('is idempotent — verifying an already-verified email returns success', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());

    $signedUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    parse_str(parse_url($signedUrl, PHP_URL_QUERY), $params);

    $this->postJson('/api/merchant/email/verify', [
        'id'        => $user->id,
        'hash'      => sha1($user->email),
        'expires'   => $params['expires'],
        'signature' => $params['signature'],
    ])->assertOk()->assertJson(['success' => true]);
});

it('returns 401 when resending verification without auth', function () {
    $this->postJson('/api/merchant/email/resend')
        ->assertStatus(401);
});
