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
                'name'  => 'Admin User',
                'email' => 'admin@gmail.com',
                'role'  => 'admin',
            ],
            [
                'name'  => 'Company User',
                'email' => 'company@gmail.com',
                'role'  => 'company',
            ],
        ];

        foreach ($users as $userData) {
            $role = Role::where('name', $userData['role'])->first();

            if ($role === null) {
                $this->command->warn("Role '{$userData['role']}' not found — skipping user '{$userData['name']}'.");
                continue;
            }

            User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name'     => $userData['name'],
                    'password' => '12345678',
                    'role_id'  => $role->id,
                ]
            );
        }
    }
}
