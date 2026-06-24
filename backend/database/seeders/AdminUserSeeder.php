<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Create the default admin user and assign the admin role.
     */
    public function run(): void
    {
        $adminId = DB::table('users')->insertGetId([
            'first_name'        => 'Admin',
            'email'             => 'admin@example.com',
            'password'          => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active'         => true,
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);

        $adminRoleId = DB::table('roles')
            ->where('name', 'admin')
            ->value('id');

        DB::table('user_roles')->insert([
            'user_id'     => $adminId,
            'role_id'     => $adminRoleId,
            'assigned_at' => now(),
        ]);
    }
}
