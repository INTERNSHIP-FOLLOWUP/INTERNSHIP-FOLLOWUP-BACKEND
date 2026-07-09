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
                    'role'     => $userData['role'],
                    'role_id'  => $role->id,
                ]
            );
        }
    }
}