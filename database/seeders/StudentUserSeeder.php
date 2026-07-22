<?php

namespace Database\Seeders;

use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\Tutor;
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
                'first_name'   => 'Serey',
                'last_name'    => 'Phem',
                'email'        => 'serey.phem@student.passerellesnumeriques.org',
                'student_code' => 'STU001',
                'gender'       => 'Male',
                'phone'        => '011 111 111',
                'status'       => 'active',
                'tutor_email'  => 'hey.him@tutor.com',
            ],
            [
                'first_name'   => 'Dane',
                'last_name'    => 'Miok',
                'email'        => 'dane.miok@student.passerellesnumeriques.org',
                'student_code' => 'STU002',
                'gender'       => 'Female',
                'phone'        => '011 222 222',
                'status'       => 'active',
                'tutor_email'  => 'yen.yon@tutor.com',
            ],
            [
                'first_name'   => 'Vicheka',
                'last_name'    => 'Hav',
                'email'        => 'vicheka.hav@student.passerellesnumeriques.org',
                'student_code' => 'STU003',
                'gender'       => 'Female',
                'phone'        => '011 333 333',
                'status'       => 'active',
                'tutor_email'  => 'meng.heang@tutor.com',
            ],
            [
                'first_name'   => 'Sreyroth',
                'last_name'    => 'Sang',
                'email'        => 'sreyroth.sang@student.passerellesnumeriques.org',
                'student_code' => 'STU004',
                'gender'       => 'Female',
                'phone'        => '011 444 555',
                'status'       => 'active',
                'tutor_email'  => 'meng.heang@tutor.com',
            ],
            [
                'first_name'   => 'Vakhim',
                'last_name'    => 'Krean',
                'email'        => 'vakhim.krean@student.passerellesnumeriques.org',
                'student_code' => 'STU005',
                'gender'       => 'Male',
                'phone'        => '011 555 555',
                'status'       => 'active',
                'tutor_email'  => 'meng.heang@tutor.com',
            ],
            [
                'first_name'   => 'Seyha',
                'last_name'    => 'Ny',
                'email'        => 'seyha.ny@student.passerellesnumeriques.org',
                'student_code' => 'STU006',
                'gender'       => 'Male',
                'phone'        => '011 666 666',
                'status'       => 'active',
                'tutor_email'  => 'meng.heang@tutor.com',
            ],
        ];

        foreach ($students as $index => $studentData) {
            $tutorId = Tutor::where('email', $studentData['tutor_email'])->value('id');
            $batchId = $batches->isNotEmpty()
                ? $batches[$index % $batches->count()]->id
                : null;

            $user = User::firstOrCreate(
                ['email' => $studentData['email']],
                [
                    'first_name' => $studentData['first_name'],
                    'last_name'  => $studentData['last_name'],
                    'email'      => $studentData['email'],
                    'password'   => '12345678',
                    'role_id'    => $studentRoleId,
                    'theme'      => 'light',
                ]
            );

            Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'user_id'      => $user->id,
                    'student_code' => $studentData['student_code'],
                    'batch_id'     => $batchId,
                    'tutor_id'     => $tutorId,
                    'first_name'   => $studentData['first_name'],
                    'last_name'    => $studentData['last_name'],
                    'gender'       => $studentData['gender'],
                    'phone'        => $studentData['phone'],
                    'email'        => $studentData['email'],
                    'status'       => $studentData['status'],
                ]
            );
        }
    }
}
