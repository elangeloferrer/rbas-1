<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Find a user by email, eager-loading roles to avoid N+1 queries.
     */
    public function findByEmail(string $email): ?User
    {
        return User::with('roles')
            ->where('email', $email)
            ->first();
    }

    /**
     * Find a user by ID, eager-loading roles.
     */
    public function findById(int $id): ?User
    {
        return User::with('roles')->find($id);
    }

    /**
     * Create a new user and return the persisted model.
     *
     *@paramarray<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::create($data);
    }

    /**
     * Update a user's attributes and return the updated model.
     *
     *@paramarray<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    /**
     * Return a paginated list of users with a given role.
     *
     *@return LengthAwarePaginator<User>
     */
    public function allByRole(string $role, int $perPage = 20): LengthAwarePaginator
    {
        return User::with('roles')
            ->whereHas('roles', fn($q) => $q->where('name', $role))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Set email_verified_at to the current timestamp.
     */
    public function markEmailVerified(User $user): void
    {
        $user->update(['email_verified_at' => now()]);
    }
}
