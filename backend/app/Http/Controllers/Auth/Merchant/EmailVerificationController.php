<?php

namespace App\Http\Controllers\Auth\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class EmailVerificationController extends Controller
{
    public function __construct(
        protected EmailVerificationService $emailVerificationService,
        protected UserRepositoryInterface  $users,
    ) {}

    /**
     * Verify the merchant's email address using the signed URL parameters.
     */
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        // Reconstruct the original signed URL so Laravel can validate the signature.
        // The Vue frontend extracted these params from the signed URL in the email
        // and POSTed them here — we rebuild the URL to verify the HMAC.
        $reconstructed = URL::route(
            'verification.verify',
            [
                'id'        => $request->id,
                'hash'      => $request->hash,
                'expires'   => $request->expires,
                'signature' => $request->signature,
            ],
            absolute: true,
        );

        // hasValidSignature validates the URL of a Request object, not a string.
        // Create a synthetic GET request from the reconstructed URL so the
        // signature check runs against the correct URL, not the current POST endpoint.
        if (! URL::hasValidSignature(Request::create($reconstructed))) {
            return $this->error('This verification link is invalid or has expired.', 403);
        }

        $user = $this->users->findById((int) $request->id);

        if (! $user) {
            return $this->error('User not found.', 404);
        }

        $this->emailVerificationService->verify($user, $request->hash);

        return $this->success('Email verified successfully. You can now log in.');
    }

    /**
     * Resend the verification email to the authenticated merchant.
     * Requires the merchant to be logged in (but not yet verified).
     */
    public function resend(Request $request): JsonResponse
    {
        $this->emailVerificationService->resend($request->user(), 'merchant');

        return $this->success('Verification email resent. Please check your inbox.');
    }
}
