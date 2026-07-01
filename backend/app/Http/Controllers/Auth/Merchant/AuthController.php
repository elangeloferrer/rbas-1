<?php

namespace App\Http\Controllers\Auth\Merchant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService              $authService,
        protected EmailVerificationService $emailVerificationService,
    ) {}

    /**
     * Register a new merchant account and send a verification email.
     * Email verification is handled by EmailVerificationService (Part 10).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated(), 'merchant');

        $this->emailVerificationService->send($user, 'merchant');

        return $this->success(
            'Account created. Please check your email to verify your address.',
            [
                'user' => [
                    'first_name' => $user->first_name,
                    'email'      => $user->email,
                    'role'       => $user->roles->first()?->name ?? 'merchant',
                ],
            ],
            201
        );
    }

    /**
     * Authenticate a merchant. Email must be verified first.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            email: $request->email,
            password: $request->password,
            roleName: 'merchant',
            requireEmailVerified: true,
        );

        return $this->success('Login successful.', [
            'user'  => [
                'first_name' => $result['user']->first_name,
                'email'      => $result['user']->email,
                'role'       => $result['user']->primaryRole() ?? 'merchant',
            ],
            // 'token' => $result['token'],
        ]);
    }

    /**
     * Log out the authenticated merchant.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return $this->success('Logged out successfully.');
    }
}
