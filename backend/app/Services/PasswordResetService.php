<?php

namespace App\Services;

use App\Exceptions\Auth\ExpiredResetTokenException;
use App\Exceptions\Auth\InvalidResetTokenException;
use App\Mail\Customer\PasswordResetMail as CustomerPasswordResetMail;
use App\Mail\Merchant\PasswordResetMail as MerchantPasswordResetMail;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService extends BaseService
{
    /**
     * Token expiry window in minutes.
     */
    private const EXPIRY_MINUTES = 60;

    public function __construct(
        protected UserRepositoryInterface $users,
    ) {}

    /**
     * Generate a reset token and send a branded email to the merchant.
     * Always returns success — never reveals whether the email exists.
     */
    public function sendResetLink(string $email, string $roleName): void
    {
        $this->attempt(function () use ($email, $roleName) {
            $user = $this->users->findByEmail($email);

            // Silently return if no user found — prevents email enumeration attacks
            if (! $user || ! $user->hasRole($roleName)) {
                return;
            }

            // Generate a cryptographically secure token
            $plainToken = Str::random(64);

            // Store a hashed version so plain tokens are never in the DB
            DB::table('password_reset_tokens')->upsert(
                [
                    'email'      => $email,
                    'token'      => Hash::make($plainToken),
                    'created_at' => now(),
                ],
                ['email'],
                ['token', 'created_at']
            );

            $frontendBase = rtrim(config('app.frontend_url'), '/');
            $resetUrl     = "{$frontendBase}/{$roleName}/reset-password?token={$plainToken}&email=" . urlencode($email);

            $mail = $roleName === 'merchant'
                ? new MerchantPasswordResetMail(resetUrl: $resetUrl, firstName: $user->first_name)
                : new CustomerPasswordResetMail(resetUrl: $resetUrl, firstName: $user->first_name);

            Mail::to($email)->send($mail);
        });
    }

    /**
     * Validate the reset token and update the user's password.
     *
     *@throwsInvalidResetTokenException
     *@throwsExpiredResetTokenException
     */
    public function resetPassword(string $email, string $token, string $newPassword): void
    {
        $this->attempt(function () use ($email, $token, $newPassword) {
            $record = DB::table('password_reset_tokens')
                ->where('email', $email)
                ->first();

            // No token on record for this email
            if (! $record) {
                throw new InvalidResetTokenException();
            }

            // Token does not match the stored hash
            if (! Hash::check($token, $record->token)) {
                throw new InvalidResetTokenException();
            }

            // Token has expired
            $createdAt = Carbon::parse($record->created_at);
            if ($createdAt->addMinutes(self::EXPIRY_MINUTES)->isPast()) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
                throw new ExpiredResetTokenException();
            }

            // Update the password
            $user = $this->users->findByEmail($email);

            if (! $user) {
                throw new InvalidResetTokenException();
            }

            $this->users->update($user, ['password' => $newPassword]);

            // Delete the used token so it cannot be reused
            DB::table('password_reset_tokens')->where('email', $email)->delete();

            // Revoke all Sanctum tokens to force re-login with the new password
            $user->tokens()->delete();
        });
    }
}
