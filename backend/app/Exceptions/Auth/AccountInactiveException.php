<?php

namespace App\Exceptions\Auth;

use Exception;

class AccountInactiveException extends Exception
{
    public function __construct()
    {
        parent::__construct('Your account has been deactivated. Please contact support.', 403);
    }
}
