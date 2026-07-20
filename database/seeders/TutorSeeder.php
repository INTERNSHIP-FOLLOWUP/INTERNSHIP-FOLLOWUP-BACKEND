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

        if ($role === null) {
            $this->command->warn("Role 'tutor' not found — skipping TutorSeeder.");
            return;
        }

        $tutors = [
            ['first_name' => 'Hey',   'last_name' => 'Him',     'email' => 'hey.him@tutor.com'],
            ['first_name' => 'Yen',   'last_name' => 'Yon',     'email' => 'yen.yon@tutor.com'],
            ['first_name' => 'Meng',  'last_name' => 'Heang',   'email' => 'meng.heang@tutor.com'],
        ];

        foreach ($tutors as $tutorData) {
            User::firstOrCreate(
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
        }
    }
}
