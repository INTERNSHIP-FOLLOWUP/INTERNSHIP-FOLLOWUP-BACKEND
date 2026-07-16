<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            [
                'name'  => 'Admin User',
                'email' => 'admin@gmail.com',
                'role'  => 'admin',
            ],
            [
                'name'  => 'Tutor User',
                'email' => 'tutor@gmail.com',
                'role'  => 'tutor',
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

            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name'     => $userData['name'],
                    'email'    => $userData['email'],
                    'password' => '12345678',
                    'role_id'  => $role->id,
                ]
            );
        }
    }
}
