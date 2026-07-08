<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class StudentUserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $role = Role::where('name', 'student')->first();

        $students = [
            [
                'name'  => 'Serey Phem',
                'email' => 'serey.phem@student.passerellesnumeriques.org',
            ],
            [
                'name'  => 'Dane Miok',
                'email' => 'dane.miok@student.passerellesnumeriques.org',
            ],
            [
                'name'  => 'Vicheka Hav',
                'email' => 'vicheka.hav@student.passerellesnumeriques.org',
            ],
        ];

        foreach ($students as $student) {
            User::firstOrCreate(
                ['email' => $student['email']],
                [
                    'name'     => $student['name'],
                    'email'    => $student['email'],
                    'password' => 'password',
                    'role'     => 'student',
                    'role_id'  => $role->id,
                ]
            );
        }
    }
}
