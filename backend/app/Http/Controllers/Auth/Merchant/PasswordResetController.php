<?php

namespace App\Http\Controllers\Auth\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(
        protected PasswordResetService $passwordResetService,
    ) {}

    /**
     * Send a password reset link to the merchant's email address.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->sendResetLink(
            email: $request->email,
            roleName: 'merchant',
        );

        // Always return success — never reveal whether the email exists
        return $this->success(
            'If an account with that email exists, a reset link has been sent.'
        );
    }

    /**
     * Reset the merchant's password using the emailed token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->passwordResetService->resetPassword(
            email: $request->email,
            token: $request->token,
            newPassword: $request->password,
        );

        return $this->success('Your password has been reset. Please log in.');
    }
}
