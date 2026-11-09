<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the administrator role and default admin account.
     */
    public function run(): void
    {
        $role = Role::updateOrCreate(
            ['name' => 'Administrator'],
            ['description' => 'Full system access'],
        );

        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'role_id' => $role->id,
                'name' => 'admin',
                'password' => Hash::make('admin'),
                'is_active' => true,
            ],
        );
    }
}
