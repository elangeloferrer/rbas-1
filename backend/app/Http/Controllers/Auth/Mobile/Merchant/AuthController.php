<?php

namespace App\Http\Controllers\Auth\Mobile\Merchant;

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
        $user = $this->authService->register($request->validated(), 'merchant');

        event(new \Illuminate\Auth\Events\Registered($user));

        return $this->success(
            'Account created. Please check your email to verify your address.',
            [
                'user' => [
                    'first_name' => $user->first_name,
                    'email'      => $user->email,
                    'role'       => $user->primaryRole() ?? 'merchant',
                ],
            ],
            201
        );
    }

    public function login(MobileLoginRequest $request): JsonResponse
    {
        $result = $this->authService->mobileLogin(
            email: $request->email,
            password: $request->password,
            roleName: 'merchant',
            deviceName: $request->device_name ?? 'mobile',
            requireEmailVerified: false,
        );

        return $this->success('Login successful.', [
            'user'  => [
                'first_name' => $result['user']->first_name,
                'email'      => $result['user']->email,
                'role'       => $result['user']->primaryRole() ?? 'merchant',
            ],
            'token' => $result['token'],
        ]);
    }
}
