<?php

namespace App\Repositories\Contracts;

use App\Models\Role;

interface RoleRepositoryInterface
{
    /**
     * Find a role by its name (e.g. 'customer', 'merchant', 'admin').
     */
    public function findByName(string $name): ?Role;

    /**
     * Assign a role to a user via the user_roles pivot table.
     */
    public function assignRoleToUser(int $userId, int $roleId): void;
}
