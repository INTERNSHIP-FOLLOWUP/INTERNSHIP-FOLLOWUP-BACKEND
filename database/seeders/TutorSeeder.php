<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use Illuminate\Database\Seeder;

class TutorSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::where('name', 'tutor')->first();

        if ($role === null) {
            $this->command->warn("Role 'tutor' not found — skipping TutorSeeder.");
            return;
        }

        $tutors = [
            [
                'first_name' => 'Hey',
                'last_name'  => 'HIM',
                'email'      => 'hey.him@tutor.com',
                'gender'     => 'Male',
                'phone'      => '012 111 111',
            ],
            [
                'first_name' => 'Yen',
                'last_name'  => 'YON',
                'email'      => 'yen.yon@tutor.com',
                'gender'     => 'Male',
                'phone'      => '012 222 222',
            ],
            [
                'first_name' => 'Meng Heang',
                'last_name'  => 'PHO',
                'email'      => 'meng.heang@tutor.com',
                'gender'     => 'Male',
                'phone'      => '012 333 333',
            ],
        ];

        foreach ($tutors as $tutorData) {
            $user = User::firstOrCreate(
                ['email' => $tutorData['email']],
                [
                    'first_name' => $tutorData['first_name'],
                    'last_name'  => $tutorData['last_name'],
                    'email'      => $tutorData['email'],
                    'password'   => '12345678',
                    'role_id'    => $role->id,
                    'theme'      => 'light',
                ]
            );

            Tutor::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id'    => $user->id,
                    'first_name' => $tutorData['first_name'],
                    'last_name'  => $tutorData['last_name'],
                    'gender'     => $tutorData['gender'],
                    'phone'      => $tutorData['phone'],
                    'email'      => $tutorData['email'],
                    'status'     => 'active',
                ]
            );
        }
    }
}
