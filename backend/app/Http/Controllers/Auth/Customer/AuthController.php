<?php

namespace App\Http\Controllers\Auth\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    /**
     * Register a new customer account.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated(), 'customer');

        return $this->success('Account created successfully.', [
            'user' => [
                'first_name' => $user->first_name,
                'email'      => $user->email,
                'role'       => $user->primaryRole() ?? 'customer',
            ],
        ], 201);
    }

    /**
     * Authenticate a customer and return a Sanctum token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            email: $request->email,
            password: $request->password,
            roleName: 'customer',
            requireEmailVerified: false,
        );

        return $this->success('Login successful.', [
            'user'  => [
                'first_name' => $result['user']->first_name,
                'email'      => $result['user']->email,
                'role'       => $result['user']->primaryRole() ?? 'customer',
            ],
            // 'token' => $result['token'],
        ]);
    }

    /**
     * Log out the authenticated customer.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return $this->success('Logged out successfully.');
    }
}
