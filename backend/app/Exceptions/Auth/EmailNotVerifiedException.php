<?php

namespace App\Exceptions\Auth;

use Exception;

class EmailNotVerifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Please verify your email address before logging in.', 403);
    }
}
