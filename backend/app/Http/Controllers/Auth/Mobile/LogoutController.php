<?php

namespace App\Http\Controllers\Auth\Mobile;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function destroy(Request $request): JsonResponse
    {
        $this->authService->mobileLogout($request->user());

        return $this->success('Logged out successfully.');
    }
}
