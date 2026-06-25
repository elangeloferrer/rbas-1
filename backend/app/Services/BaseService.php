<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

abstract class BaseService
{
    /**
     * Execute a callable and log any exception that escapes it.
     * Re-throws the exception so the controller can handle it.
     *
     *@template T
     *@paramcallable(): T  $callback
     *@return T
     *
     *@throwsException
     */
    protected function attempt(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Exception $e) {
            Log::error(static::class . ' error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            throw $e;
        }
    }
}
