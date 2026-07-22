<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Admin',
                'last_name'  => 'User',
                'email' => 'admin@gmail.com',
                'role'  => 'admin',
            ],
            [
                'first_name' => 'Company',
                'last_name'  => 'User',
                'email' => 'company@gmail.com',
                'role'  => 'company',
            ],
        ];

        foreach ($users as $userData) {
            $role = Role::where('name', $userData['role'])->first();

            if ($role === null) {
                $this->command->warn("Role '{$userData['role']}' not found — skipping user '{$userData['first_name']} {$userData['last_name']}'.");
                continue;
            }

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'first_name' => $userData['first_name'],
                    'last_name'  => $userData['last_name'],
                    'email'      => $userData['email'],
                    'password'   => '12345678',
                    'role_id'    => $role->id,
                    'theme'      => 'light',
                ]
            );
        }
    }
}
