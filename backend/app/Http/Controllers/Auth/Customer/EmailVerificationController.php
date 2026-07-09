<?php

namespace App\Http\Controllers\Auth\Customer;

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
     * Verify the customer's email address using the signed URL parameters.
     */
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
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

        if (! URL::hasValidSignature(Request::create($reconstructed))) {
            return $this->error('This verification link is invalid or has expired.', 403);
        }

        $user = $this->users->findById((int) $request->id);

        if (! $user || ! $user->hasRole('customer')) {
            return $this->error('User not found.', 404);
        }

        $this->emailVerificationService->verify($user, $request->hash);

        return $this->success('Email verified successfully. You can now log in.');
    }

    /**
     * Resend the verification email to the authenticated customer.
     * Requires an active session — customers are auto-logged in during registration,
     * so a session always exists when this is called from /verify-email.
     */
    public function resend(Request $request): JsonResponse
    {
        $this->emailVerificationService->resend($request->user(), 'customer');

        return $this->success('Verification email resent. Please check your inbox.');
    }
}
