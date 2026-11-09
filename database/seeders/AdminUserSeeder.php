<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the default roles and user accounts.
     */
    public function run(): void
    {
        $roles = [
            'Administrator' => 'Full system access',
            'Procurement Officer' => 'Can create and manage purchase orders',
            'Viewer' => 'Read-only access to all modules',
        ];

        foreach ($roles as $name => $description) {
            Role::updateOrCreate(
                ['name' => $name],
                ['description' => $description],
            );
        }

        $users = [
            [
                'name' => 'admin',
                'email' => 'admin@example.com',
                'password' => 'admin',
                'role' => 'Administrator',
            ],
            [
                'name' => 'user1',
                'email' => 'user1@example.com',
                'password' => 'user1',
                'role' => 'Procurement Officer',
            ],
            [
                'name' => 'user2',
                'email' => 'user2@example.com',
                'password' => 'user2',
                'role' => 'Viewer',
            ],
        ];

        foreach ($users as $user) {
            $role = Role::where('name', $user['role'])->firstOrFail();

            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'role_id' => $role->id,
                    'name' => $user['name'],
                    'password' => Hash::make($user['password']),
                    'is_active' => true,
                ],
            );
        }
    }
}
