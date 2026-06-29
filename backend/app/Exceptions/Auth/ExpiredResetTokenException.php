<?php

namespace App\Exceptions\Auth;

use Exception;

class ExpiredResetTokenException extends Exception
{
    public function __construct()
    {
        parent::__construct('This password reset link has expired. Please request a new one.', 422);
    }
}
