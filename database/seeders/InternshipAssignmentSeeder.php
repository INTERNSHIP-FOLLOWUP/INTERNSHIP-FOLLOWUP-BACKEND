<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\InternshipAssignment;
use App\Models\Student;
use App\Models\Tutor;
use Illuminate\Database\Seeder;

class InternshipAssignmentSeeder extends Seeder
{
    public function run(): void
    {
        $students = Student::all();
        $companies = Company::all();
        $tutors = Tutor::all();

        if ($students->isEmpty() || $companies->isEmpty() || $tutors->isEmpty()) {
            $this->command->warn('Missing students, companies, or tutors — skipping InternshipAssignmentSeeder.');
            return;
        }

        $assignments = [
            [
                'student_index' => 0,
                'company_index' => 0,
                'tutor_index' => 0,
                'position' => 'Software Developer Intern',
                'start_date' => '2025-06-01',
                'end_date' => '2025-12-31',
                'status' => 'In Progress',
            ],
            [
                'student_index' => 1,
                'company_index' => 0,
                'tutor_index' => 1,
                'position' => 'Frontend Developer Intern',
                'start_date' => '2025-06-15',
                'end_date' => '2025-12-15',
                'status' => 'In Progress',
            ],
            [
                'student_index' => 2,
                'company_index' => 1,
                'tutor_index' => 2,
                'position' => 'Digital Marketing Intern',
                'start_date' => '2025-01-10',
                'end_date' => '2025-06-30',
                'status' => 'Completed',
            ],
            [
                'student_index' => 3,
                'company_index' => 1,
                'tutor_index' => 2,
                'position' => 'Content Writer Intern',
                'start_date' => '2025-07-01',
                'end_date' => '2025-12-31',
                'status' => 'In Progress',
            ],
            [
                'student_index' => 4,
                'company_index' => 0,
                'tutor_index' => 2,
                'position' => 'QA Engineer Intern',
                'start_date' => '2025-03-01',
                'end_date' => '2025-08-31',
                'status' => 'Completed',
            ],
            [
                'student_index' => 5,
                'company_index' => 0,
                'tutor_index' => 2,
                'position' => 'Backend Developer Intern',
                'start_date' => '2025-09-01',
                'end_date' => '2026-03-31',
                'status' => 'Assigned',
            ],
        ];

        foreach ($assignments as $a) {
            $student = $students[$a['student_index']];
            $company = $companies[$a['company_index']];
            $tutor = $tutors[$a['tutor_index']];

            InternshipAssignment::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'company_id' => $company->id,
                ],
                [
                    'student_id' => $student->id,
                    'company_id' => $company->id,
                    'tutor_id' => $tutor->id,
                    'position' => $a['position'],
                    'start_date' => $a['start_date'],
                    'end_date' => $a['end_date'],
                    'status' => $a['status'],
                ]
            );
        }
    }
}
