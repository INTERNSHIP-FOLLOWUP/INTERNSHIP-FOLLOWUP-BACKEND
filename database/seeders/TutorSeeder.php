<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class TutorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'tutor')->first();

        $tutors = [
            [
                'name'  => 'HEY him',
                'email' => 'hey.him@tutor.com',
            ],
            [
                'name'  => 'YEN yon',
                'email' => 'yen.yon@tutor.com',
            ],
            [
                'name'  => 'MENG HEANG',
                'email' => 'meng.heang@tutor.com',
            ],
        ];

        foreach ($tutors as $tutorData) {
            User::firstOrCreate(
                ['email' => $tutorData['email']],
                [
                    'name'     => $tutorData['name'],
                    'email'    => $tutorData['email'],
                    'password' => '12345678',
                    'role_id'  => $role->id,
                ]
            );
        }
    }
}
