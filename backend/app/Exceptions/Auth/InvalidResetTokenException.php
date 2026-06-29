<?php

namespace App\Exceptions\Auth;

use Exception;

class InvalidResetTokenException extends Exception
{
    public function __construct()
    {
        parent::__construct('This password reset link is invalid.', 422);
    }
}
