<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Find a user by their email address, eager-loading their roles.
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find a user by their ID, eager-loading their roles.
     */
    public function findById(int $id): ?User;

    /**
     * Create a new user record and return the persisted model.
     *
     *@paramarray<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * Update an existing user record.
     *
     *@paramarray<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    /**
     * Return all users that have the given role name, paginated.
     *
     *@return\Illuminate\Pagination\LengthAwarePaginator<User>
     */
    public function allByRole(string $role, int $perPage = 20): mixed;

    /**
     * Mark the user's email as verified right now.
     */
    public function markEmailVerified(User $user): void;
}
