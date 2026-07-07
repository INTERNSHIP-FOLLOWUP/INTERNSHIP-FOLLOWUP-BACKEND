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
                'email' => 'admin@example.com',
                'role'  => 'admin',
            ],
            [
                'name'  => 'Tutor User',
                'email' => 'tutor@example.com',
                'role'  => 'tutor',
            ],
            [
                'name'  => 'Student User',
                'email' => 'student@example.com',
                'role'  => 'student',
            ],
            [
                'name'  => 'Company User',
                'email' => 'company@example.com',
                'role'  => 'company representative',
            ],
        ];

        foreach ($users as $userData) {
            $role = Role::where('name', $userData['role'])->first();

            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name'     => $userData['name'],
                    'email'    => $userData['email'],
                    'password' => 'password',
                    'role_id'  => $role->id,
                ]
            );
        }
    }
}