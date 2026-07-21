<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentUserSeeder extends Seeder
{
    public function run(): void
    {
        $studentRoleId = Role::where('name', 'student')->value('id');
        $batches = Batch::all();

        $students = [
            [
                'name'         => 'Serey Phem',
                'email'        => 'serey.phem@student.passerellesnumeriques.org',
                'student_code' => 'STU-001',
                'gender'       => 'Male',
                'phone'        => '011 111 111',
                'status'       => 'active',
                'tutor_email'  => 'hey.him@tutor.com',
            ],
            [
                'name'         => 'Dane Miok',
                'email'        => 'dane.miok@student.passerellesnumeriques.org',
                'student_code' => 'STU-002',
                'gender'       => 'Female',
                'phone'        => '011 222 222',
                'status'       => 'active',
                'tutor_email'  => 'yen.yon@tutor.com',
            ],
            [
                'name'         => 'Vicheka Hav',
                'email'        => 'vicheka.hav@student.passerellesnumeriques.org',
                'student_code' => 'STU-003',
                'gender'       => 'Female',
                'phone'        => '011 333 333',
                'status'       => 'active',
                'tutor_email'  => 'meng.heang@tutor.com',
            ],
            [
                'name'         => 'Sreyroth Sang',
                'email'        => 'sreyroth.sang@student.passerellesnumeriques.org',
                'student_code' => 'STU-004',
                'gender'       => 'Female',
                'phone'        => '011 444 555',
                'status'       => 'active',
                'tutor_email'  => 'meng.heang@tutor.com',
            ],
            [
                'name'         => 'Vakhim Krean',
                'email'        => 'vakhim.krean@student.passerellesnumeriques.org',
                'student_code' => 'STU-005',
                'gender'       => 'Male',
                'phone'        => '011 555 555',
                'status'       => 'active',
                'tutor_email'  => 'meng.heang@tutor.com',
            ],
            [
                'name'         => 'Seyha Ny',
                'email'        => 'seyha.ny@student.passerellesnumeriques.org',
                'student_code' => 'STU-006',
                'gender'       => 'Male',
                'phone'        => '011 666 666',
                'status'       => 'active',
                'tutor_email'  => 'meng.heang@tutor.com',
            ],
        ];

        foreach ($students as $index => $studentData) {
            $tutorId = User::where('email', $studentData['tutor_email'])->value('id');
            $batchId = $batches->isNotEmpty()
                ? $batches[$index % $batches->count()]->id
                : null;

            $user = User::updateOrCreate(
                ['email' => $studentData['email']],
                [
                    'name'     => $studentData['name'],
                    'password' => '12345678',
                    'role_id'  => $studentRoleId,
                ]
            );

            Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id'      => $user->id,
                    'student_code' => $studentData['student_code'],
                    'batch_id'     => $batchId,
                    'tutor_id'     => $tutorId,
                    'name'         => $studentData['name'],
                    'gender'       => $studentData['gender'],
                    'phone'        => $studentData['phone'],
                    'email'        => $studentData['email'],
                    'status'       => $studentData['status'],
                ]
            );
        }
    }
}
