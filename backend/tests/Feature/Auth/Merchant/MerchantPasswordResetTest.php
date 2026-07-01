<?php

use App\Models\User;
use App\Mail\Merchant\PasswordResetMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

it('sends a reset email to a valid merchant email', function () {
    $user = User::factory()->create(['email' => 'sam@merchant.com']);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());

    $this->postJson('/api/merchant/forgot-password', [
        'email' => 'sam@merchant.com',
    ])->assertOk()->assertJson(['success' => true]);

    Mail::assertQueued(
        PasswordResetMail::class,
        fn($mail) => $mail->hasTo('sam@merchant.com')
    );
});

it('returns success even for a non-existent email (enumeration protection)', function () {
    $this->postJson('/api/merchant/forgot-password', [
        'email' => 'ghost@nobody.com',
    ])->assertOk()->assertJson(['success' => true]);

    Mail::assertNothingOutgoing();
});

it('returns success even for wrong-role email (enumeration protection)', function () {
    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $admin->roles()->attach(\App\Models\Role::where('name', 'admin')->first());

    $this->postJson('/api/merchant/forgot-password', [
        'email' => 'admin@example.com',
    ])->assertOk()->assertJson(['success' => true]);

    Mail::assertNothingOutgoing();
});

it('resets the password with a valid token', function () {
    $user = User::factory()->create(['email' => 'sam@merchant.com']);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());

    $plainToken = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email'      => 'sam@merchant.com',
        'token'      => Hash::make($plainToken),
        'created_at' => now(),
    ]);

    $this->postJson('/api/merchant/reset-password', [
        'token'                 => $plainToken,
        'email'                 => 'sam@merchant.com',
        'password'              => 'NewPassword1',
        'password_confirmation' => 'NewPassword1',
    ])->assertOk()->assertJson(['success' => true]);

    expect(Hash::check('NewPassword1', $user->fresh()->password))->toBeTrue();
});

it('rejects login with the old password after reset', function () {
    $user = User::factory()->create([
        'email'    => 'sam@merchant.com',
        'password' => bcrypt('OldPassword1'),
    ]);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());

    $plainToken = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email'      => 'sam@merchant.com',
        'token'      => Hash::make($plainToken),
        'created_at' => now(),
    ]);

    $this->postJson('/api/merchant/reset-password', [
        'token'                 => $plainToken,
        'email'                 => 'sam@merchant.com',
        'password'              => 'NewPassword1',
        'password_confirmation' => 'NewPassword1',
    ])->assertOk();

    $this->postJson('/api/merchant/login', [
        'email'    => 'sam@merchant.com',
        'password' => 'OldPassword1',
    ])->assertStatus(401);
});

it('cannot reuse a token after a successful reset', function () {
    $user = User::factory()->create(['email' => 'sam@merchant.com']);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());

    $plainToken = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email'      => 'sam@merchant.com',
        'token'      => Hash::make($plainToken),
        'created_at' => now(),
    ]);

    $payload = [
        'token'                 => $plainToken,
        'email'                 => 'sam@merchant.com',
        'password'              => 'NewPassword1',
        'password_confirmation' => 'NewPassword1',
    ];

    $this->postJson('/api/merchant/reset-password', $payload)->assertOk();

    // Second attempt with the same token
    $this->postJson('/api/merchant/reset-password', $payload)->assertStatus(422);
});

it('rejects an invalid token', function () {
    $this->postJson('/api/merchant/reset-password', [
        'token'                 => str_repeat('x', 64),
        'email'                 => 'sam@merchant.com',
        'password'              => 'NewPassword1',
        'password_confirmation' => 'NewPassword1',
    ])->assertStatus(422)->assertJson(['success' => false]);
});

it('rejects an expired token', function () {
    $user = User::factory()->create(['email' => 'sam@merchant.com']);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());

    $plainToken = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email'      => 'sam@merchant.com',
        'token'      => Hash::make($plainToken),
        'created_at' => now()->subMinutes(61), // expired
    ]);

    $this->postJson('/api/merchant/reset-password', [
        'token'                 => $plainToken,
        'email'                 => 'sam@merchant.com',
        'password'              => 'NewPassword1',
        'password_confirmation' => 'NewPassword1',
    ])->assertStatus(422);
});

it('rejects a weak password on reset', function () {
    $this->postJson('/api/merchant/reset-password', [
        'token'                 => str_repeat('x', 64),
        'email'                 => 'sam@merchant.com',
        'password'              => 'weak',
        'password_confirmation' => 'weak',
    ])->assertStatus(422)->assertJson(['success' => false]);
});

it('rejects mismatched password confirmation', function () {
    $this->postJson('/api/merchant/reset-password', [
        'token'                 => str_repeat('x', 64),
        'email'                 => 'sam@merchant.com',
        'password'              => 'NewPassword1',
        'password_confirmation' => 'DifferentPassword1',
    ])->assertStatus(422)->assertJson(['success' => false]);
});

it('deletes the token and revokes all sanctum tokens after reset', function () {
    $user = User::factory()->create(['email' => 'sam@merchant.com']);
    $user->roles()->attach(\App\Models\Role::where('name', 'merchant')->first());
    $user->createToken('test-device');

    $plainToken = Str::random(64);
    DB::table('password_reset_tokens')->insert([
        'email'      => 'sam@merchant.com',
        'token'      => Hash::make($plainToken),
        'created_at' => now(),
    ]);

    $this->postJson('/api/merchant/reset-password', [
        'token'                 => $plainToken,
        'email'                 => 'sam@merchant.com',
        'password'              => 'NewPassword1',
        'password_confirmation' => 'NewPassword1',
    ])->assertOk();

    expect(DB::table('password_reset_tokens')->where('email', 'sam@merchant.com')->exists())->toBeFalse();
    expect($user->fresh()->tokens()->count())->toBe(0);
});
