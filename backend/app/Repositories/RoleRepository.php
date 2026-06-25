<?php

namespace App\Repositories;

use App\Models\Role;
use App\Repositories\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RoleRepository implements RoleRepositoryInterface
{
    /**
     * Find a role record by its name.
     */
    public function findByName(string $name): ?Role
    {
        return Role::where('name', $name)->first();
    }

    /**
     * Insert a row into user_roles linking the user to the role.
     */
    public function assignRoleToUser(int $userId, int $roleId): void
    {
        DB::table('user_roles')->insertOrIgnore([
            'user_id'     => $userId,
            'role_id'     => $roleId,
            'assigned_at' => now(),
        ]);
    }
}
