<?php

namespace Tests\Helpers;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

trait MerchantTestHelper
{
    // Plain-text password that satisfies Password::min(8)->mixedCase()->numbers()
    protected string $password = 'Password1!';

    protected function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    protected function createMerchant(array $overrides = []): User
    {
        return $this->createUserWithRole('merchant', $overrides);
    }

    /** Merchant with no email_verified_at — soft-gate login still works; dashboard may block. */
    protected function createUnverifiedMerchant(array $overrides = []): User
    {
        return $this->createMerchant(array_merge(['email_verified_at' => null], $overrides));
    }

    /** Merchant with is_active = false — blocked by the EnsureUserIsActive middleware. */
    protected function createInactiveMerchant(array $overrides = []): User
    {
        return $this->createMerchant(array_merge(['is_active' => false], $overrides));
    }

    protected function createCustomer(array $overrides = []): User
    {
        return $this->createUserWithRole('customer', $overrides);
    }

    protected function createAdmin(array $overrides = []): User
    {
        return $this->createUserWithRole('admin', $overrides);
    }

    /** Authenticate as a merchant for SPA (session-based) requests. */
    protected function actingAsMerchant(User $user): static
    {
        return $this->actingAs($user, 'sanctum');
    }

    /**
     * Build the POST body for /api/merchant/email/verify from a signed URL.
     * Mirrors exactly how the Vue frontend extracts params from the emailed link.
     */
    protected function signedVerifyParams(User $user, int $expiresMinutes = 1440): array
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes($expiresMinutes),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $parsed = parse_url($url);
        parse_str($parsed['query'] ?? '', $query);

        return [
            'id' => $user->id,
            'hash' => sha1($user->email),
            'expires' => (int) $query['expires'],
            'signature' => $query['signature'],
        ];
    }

    /**
     * Insert a raw reset token into password_reset_tokens and return the plain token.
     * Mirrors how PasswordResetService stores it.
     */
    protected function createResetToken(User $user, ?int $ageMinutes = null): string
    {
        $plainToken = Str::random(64);

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($plainToken),
            // Use !== null so that ageMinutes: 0 is treated as "just created", not omitted.
            'created_at' => $ageMinutes !== null ? now()->subMinutes($ageMinutes) : now(),
        ]);

        return $plainToken;
    }

    /** Valid registration payload — override any field you need. */
    protected function registrationPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Jane',
            'email' => 'jane@merchant.test',
            'password' => $this->password,
            'password_confirmation' => $this->password,
        ], $overrides);
    }

    /** Shared factory: create a verified active user and attach the given role. */
    private function createUserWithRole(string $roleName, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'password' => Hash::make($this->password),
            'email_verified_at' => now(),
            'is_active' => true,
        ], $overrides));

        $role = Role::where('name', $roleName)->firstOrFail();
        $user->roles()->attach($role->id, ['assigned_at' => now()]);
        $user->load('roles');

        return $user;
    }
}
