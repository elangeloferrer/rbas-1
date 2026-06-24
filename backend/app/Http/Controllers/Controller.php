<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /**
     * Return a successful JSON response.
     *
     *@parammixed  $data
     */
    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $payload = [
            'success' => true,
            'message' => $message,
        ];

        if (! is_null($data)) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return an error JSON response.
     *
     *@paramarray<string, mixed>|null  $errors
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if (! is_null($errors)) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }
}
