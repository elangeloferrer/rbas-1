<?php

namespace App\Services;

use App\Mail\Customer\VerifyEmailMail as CustomerVerifyEmailMail;
use App\Mail\Merchant\VerifyEmailMail as MerchantVerifyEmailMail;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class EmailVerificationService extends BaseService
{
    /**
     * Signed URL expiry in minutes (24 hours).
     */
    private const EXPIRY_MINUTES = 1440;

    public function __construct(
        protected UserRepositoryInterface $users,
    ) {}

    /**
     * Generate a signed verification URL and send the appropriate branded email.
     */
    public function send(User $user, string $roleName): void
    {
        $this->attempt(function () use ($user, $roleName) {
            $verificationUrl = $this->buildFrontendVerificationUrl($user, $roleName);

            if ($roleName === 'merchant') {
                Mail::to($user->email)->send(new MerchantVerifyEmailMail(
                    verificationUrl: $verificationUrl,
                    firstName: $user->first_name,
                ));
            } else {
                Mail::to($user->email)->send(new CustomerVerifyEmailMail(
                    verificationUrl: $verificationUrl,
                    firstName: $user->first_name,
                ));
            }
        });
    }

    /**
     * Verify the signed URL and mark the user's email as verified.
     *
     *@throws\Illuminate\Auth\Access\AuthorizationException
     */
    public function verify(User $user, string $hash): void
    {
        $this->attempt(function () use ($user, $hash) {
            // Check the hash matches the user's email
            if (! hash_equals(sha1($user->email), $hash)) {
                abort(403, 'Invalid verification link.');
            }

            // Already verified — nothing to do
            if (! is_null($user->email_verified_at)) {
                return;
            }

            $this->users->markEmailVerified($user);
        });
    }

    /**
     * Resend the verification email if the user is not yet verified.
     */
    public function resend(User $user, string $roleName): void
    {
        $this->attempt(function () use ($user, $roleName) {
            if (! is_null($user->email_verified_at)) {
                return; // Already verified — silently do nothing
            }

            $this->send($user, $roleName);
        });
    }

    /**
     * Build the frontend verification URL.
     * Generates the HMAC query string via a temporary signed route, then composes
     * the full frontend URL directly — no str_replace, no env() call.
     */
    private function buildFrontendVerificationUrl(User $user, string $roleName): string
    {
        $emailHash = sha1($user->email); // computed once, used in both places below

        $signedRoute = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(self::EXPIRY_MINUTES),
            ['id' => $user->id, 'hash' => $emailHash],
        );

        // Extract only the HMAC query string (expires + signature) from the signed URL.
        // The frontend URL is built directly from known parts — no string replacement needed.
        $query        = parse_url($signedRoute, PHP_URL_QUERY); // expires=...&signature=...
        $frontendBase = rtrim(config('app.frontend_url'), '/');

        return "{$frontendBase}/{$roleName}/email/verify/{$user->id}/{$emailHash}?{$query}";
    }
}
