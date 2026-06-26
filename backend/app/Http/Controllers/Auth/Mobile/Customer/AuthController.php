<?php

namespace App\Http\Controllers\Auth\Mobile\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MobileLoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

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

    public function login(MobileLoginRequest $request): JsonResponse
    {
        $result = $this->authService->mobileLogin(
            email: $request->email,
            password: $request->password,
            roleName: 'customer',
            deviceName: $request->device_name ?? 'mobile',
            requireEmailVerified: false,
        );

        return $this->success('Login successful.', [
            'user'  => [
                'first_name' => $result['user']->first_name,
                'email'      => $result['user']->email,
                'role'       => $result['user']->primaryRole() ?? 'customer',
            ],
            'token' => $result['token'],
        ]);
    }
}
