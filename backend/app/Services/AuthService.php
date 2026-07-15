<?php

namespace App\Services;

use App\Exceptions\Auth\AccountInactiveException;
use App\Exceptions\Auth\EmailNotVerifiedException;
use App\Exceptions\Auth\InvalidCredentialsException;
use App\Exceptions\Auth\RoleNotFoundException;
use App\Models\User;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService extends BaseService
{
    public function __construct(
        protected UserRepositoryInterface $users,
        protected RoleRepositoryInterface $roles,
    ) {}

    /**
     * Register a new user with the given role and return the user model.
     *
     *@paramarray<string, string>  $data
     *
     *@throwsRoleNotFoundException
     */
    public function register(array $data, string $roleName): User
    {
        return $this->attempt(function () use ($data, $roleName) {
            $role = $this->roles->findByName($roleName);

            if (! $role) {
                throw new RoleNotFoundException($roleName);
            }

            $user = $this->users->create([
                'first_name' => $data['first_name'],
                'email' => $data['email'],
                'password' => $data['password'], // cast to 'hashed' in User model
                'is_active' => true,
            ]);

            $this->roles->assignRoleToUser($user->id, $role->id);

            return $user->load('roles');
        });
    }

    /**
     * Validate credentials for a user
     * For SPA cookie auth — the session handles auth.
     *
     * @return array{ user: User, token: string }
     *
     *@throwsInvalidCredentialsException
     *
     *@throwsAccountInactiveException
     *
     *@throwsEmailNotVerifiedException
     */
    public function login(string $email, string $password, string $roleName, bool $requireEmailVerified = false): array
    {
        return $this->attempt(function () use ($email, $password, $roleName) {
            $user = $this->users->findByEmail($email);

            // Wrong email or wrong password
            if (! $user || ! Hash::check($password, $user->password)) {
                throw new InvalidCredentialsException;
            }

            // User does not have the required role for this login endpoint
            if (! $user->hasRole($roleName)) {
                throw new InvalidCredentialsException;
            }

            // Account deactivated by admin
            if (! $user->is_active) {
                throw new AccountInactiveException;
            }

            // // Email not verified
            // if ($requireEmailVerified && is_null($user->email_verified_at)) {
            //     throw new EmailNotVerifiedException();
            // }

            // SPA (Vue): start an httpOnly session — no token exposed to JS
            Auth::login($user);

            return ['user' => $user];
        });
    }

    /**
     * SPA logout — destroy the session and prevent session fixation.
     */
    public function logout(Request $request): void
    {
        $this->attempt(function () use ($request) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        });
    }

    // -------------------------------------------------------------------------
    // Mobile (token-based) auth
    // -------------------------------------------------------------------------

    /**
     * Validate credentials and issue a Sanctum personal access token for mobile clients.
     * No session is started — the token is the sole credential.
     *
     * @param  string  $deviceName  Identifies the device (e.g. "iPhone 15 Pro").
     *                              Passed from the request body; defaults to "mobile".
     * @return array{ user: User, token: string }
     *
     * @throws InvalidCredentialsException
     * @throws AccountInactiveException
     * @throws EmailNotVerifiedException
     */
    public function mobileLogin(
        string $email,
        string $password,
        string $roleName,
        string $deviceName = 'mobile',
        bool $requireEmailVerified = false,
    ): array {
        return $this->attempt(function () use ($email, $password, $roleName, $deviceName, $requireEmailVerified) {
            $user = $this->users->findByEmail($email);

            // Wrong email or wrong password
            if (! $user || ! Hash::check($password, $user->password)) {
                throw new InvalidCredentialsException;
            }

            // User does not have the required role for this login endpoint
            if (! $user->hasRole($roleName)) {
                throw new InvalidCredentialsException;
            }

            // Account deactivated by admin
            if (! $user->is_active) {
                throw new AccountInactiveException;
            }

            // Email not verified
            if ($requireEmailVerified && is_null($user->email_verified_at)) {
                throw new EmailNotVerifiedException;
            }

            $roleName = $user->primaryRole() ?? 'user';

            // Revoke any existing token for this device (one-token-per-device).
            // Remove this line to allow multiple simultaneous device sessions.
            $user->tokens()->where('name', "{$deviceName}-{$roleName}")->delete();

            $token = $user->createToken(
                name: "{$deviceName}-{$roleName}",
                abilities: [$roleName],
            )->plainTextToken;

            return ['user' => $user, 'token' => $token];
        });
    }

    /**
     * Mobile logout — revoke only the current request's token.
     * Other device tokens remain valid (multi-device support).
     */
    public function mobileLogout(User $user): void
    {
        $this->attempt(function () use ($user) {
            $user->currentAccessToken()?->delete();
        });
    }
}
