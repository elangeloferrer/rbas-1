<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Seed the roles table with the three application roles.
     */
    public function run(): void
    {
        $roles = [
            [
                'name'        => 'customer',
                'description' => 'End user who browses and purchases.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'merchant',
                'description' => 'Business owner who lists products.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'name'        => 'admin',
                'description' => 'Platform administrator with full access.',
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        DB::table('roles')->upsert(
            $roles,
            ['name'],                        // unique key to check against
            ['description', 'updated_at']    // columns to update on duplicate
        );
    }
}
