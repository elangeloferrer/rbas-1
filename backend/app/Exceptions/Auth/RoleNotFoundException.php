<?php

namespace App\Exceptions\Auth;

use Exception;

class RoleNotFoundException extends Exception
{
    public function __construct(string $role)
    {
        parent::__construct("Role '{$role}' does not exist in the database.", 500);
    }
}
