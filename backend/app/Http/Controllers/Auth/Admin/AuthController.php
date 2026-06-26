<?php

namespace App\Http\Controllers\Auth\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    /**
     * Authenticate an admin user.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            email: $request->email,
            password: $request->password,
            roleName: 'admin',
            requireEmailVerified: false,
        );

        return $this->success('Login successful.', [
            'user' => [
                'first_name' => $result['user']->first_name,
                'email'      => $result['user']->email,
                'role'       => $result['user']->primaryRole() ?? 'admin',
            ],
        ]);
    }

    /**
     * Log out the authenticated admin.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout();

        return $this->success('Logged out successfully.');
    }
}
