<?php

namespace Database\Seeders;

use App\Models\{Role, User, Student, Batch, Company, InternshipAssignment, Worklog, WorklogReview, Attachment, Followup, Issue};
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TutorDashboardSeeder extends Seeder
{
    private int $targetStudents = 8;
    private int $desiredDueSoonFollowups = 3;
    private int $desiredOpenIssues = 4;

    public function run(): void
    {
        $tutor = User::whereHas('role', fn ($q) => $q->where('name', 'tutor'))
            ->where('name', 'MENG HEANG')
            ->orWhere('email', 'meng.heang@tutor.com')
            ->first();

        $batches = Batch::all();
        if ($batches->isEmpty()) {
            $this->call(\Database\Seeders\BatchSeeder::class);
            $batches = Batch::all();
        }

        $companies = Company::all();
        if ($companies->isEmpty()) {
            $this->call(\Database\Seeders\CompanySeeder::class);
            $companies = Company::all();
        }

        $students = $this->seedStudents($tutor, $batches);
        $this->seedAssignments($students, $companies, $tutor);
        $this->seedWorklogs($students, $tutor);
        $this->seedFollowups($students, $tutor);
        $this->seedIssues($students, $tutor);
    }

    private function seedTutorUserIfMissing(): ?User
    {
        $tutor = User::whereHas('role', fn ($q) => $q->where('name', 'tutor'))
            ->where('name', 'MENG HEANG')
            ->orWhere('email', 'meng.heang@tutor.com')
            ->first();

        if ($tutor) {
            return $tutor;
        }

        $role = Role::where('name', 'tutor')->first();

        if (! $role) {
            return null;
        }

        return User::firstOrCreate(
            ['email' => 'meng.heang@tutor.com'],
            [
                'name'     => 'MENG HEANG',
                'password' => Hash::make('password'),
                'role_id'  => $role->id,
            ]
        );
    }

    private function seedStudents(User $tutor, $batches): \Illuminate\Database\Eloquent\Collection
    {
        $studentRoleId = Role::where('name', 'student')->value('id');
        $existing = Student::where('tutor_id', $tutor->id)->get();

        if ($existing->count() >= $this->targetStudents) {
            return $existing;
        }

        $students = $existing;
        $needed = $this->targetStudents - $existing->count();

        $blueprints = [
            ['name' => 'Angela Rin', 'email' => 'angela.rin@students.example.org', 'code' => 'STU-TR-01', 'gender' => 'Female', 'status' => 'active'],
            ['name' => 'Bopha Lim', 'email' => 'bopha.lim@students.example.org', 'code' => 'STU-TR-02', 'gender' => 'Female', 'status' => 'active'],
            ['name' => 'Chhay Doeurn', 'email' => 'chhay.d@students.example.org', 'code' => 'STU-TR-03', 'gender' => 'Male', 'status' => 'active'],
            ['name' => 'Dara Phou', 'email' => 'dara.phou@students.example.org', 'code' => 'STU-TR-04', 'gender' => 'Male', 'status' => 'active'],
            ['name' => 'Dina Ouk', 'email' => 'dina.ouk@students.example.org', 'code' => 'STU-TR-05', 'gender' => 'Female', 'status' => 'inactive'],
            ['name' => 'Ean Khiev', 'email' => 'ean.khiev@students.example.org', 'code' => 'STU-TR-06', 'gender' => 'Male', 'status' => 'active'],
            ['name' => 'Heng Mom', 'email' => 'heng.mom@students.example.org', 'code' => 'STU-TR-07', 'gender' => 'Male', 'status' => 'active'],
            ['name' => 'Kaliya Suy', 'email' => 'kaliya.suy@students.example.org', 'code' => 'STU-TR-08', 'gender' => 'Female', 'status' => 'active'],
            ['name' => 'Mony Srean', 'email' => 'mony.srean@students.example.org', 'code' => 'STU-TR-09', 'gender' => 'Male', 'status' => 'active'],
            ['name' => 'Sokha Ly', 'email' => 'sokha.ly@students.example.org', 'code' => 'STU-TR-10', 'gender' => 'Female', 'status' => 'active'],
        ];

        foreach (array_slice($blueprints, 0, min($needed, count($blueprints))) as $blueprint) {
            $user = User::firstOrCreate(
                ['email' => $blueprint['email']],
                [
                    'name'     => $blueprint['name'],
                    'email'    => $blueprint['email'],
                    'password' => Hash::make('password'),
                    'role_id'  => $studentRoleId,
                ]
            );

            $student = Student::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'student_code' => $blueprint['code'],
                    'batch_id'     => $batches->random()->id,
                    'tutor_id'     => $tutor->id,
                    'name'         => $blueprint['name'],
                    'gender'       => $blueprint['gender'],
                    'phone'        => fake()->phoneNumber(),
                    'email'        => $blueprint['email'],
                    'photo'        => null,
                    'status'       => $blueprint['status'],
                ]
            );

            $students->push($student);
        }

        return $students;
    }

    private function seedAssignments($students, $companies, User $tutor): void
    {
        foreach ($students as $student) {
            if ($student->internshipAssignment()->exists()) {
                continue;
            }

            InternshipAssignment::create([
                'student_id' => $student->id,
                'company_id' => $companies->random()->id,
                'tutor_id'   => $tutor->id,
                'position'   => fake()->randomElement([
                    'Frontend Developer Intern',
                    'Backend Developer Intern',
                    'Marketing Intern',
                    'QA Intern',
                    'Design Intern',
                ]),
                'start_date' => Carbon::now()->subMonths(fake()->numberBetween(1, 5))->startOfMonth()->toDateString(),
                'end_date'   => Carbon::now()->addMonths(fake()->numberBetween(1, 4))->endOfMonth()->toDateString(),
                'status'     => fake()->randomElement(['Assigned', 'In Progress', 'In Progress', 'Completed', 'Terminated']),
            ]);
        }
    }

    private function seedWorklogs($students, User $tutor): void
    {
        foreach ($students as $student) {
            $count = min(5, max(0, fake()->numberBetween(0, 5)));

            if ($count === 0) {
                continue;
            }

            for ($i = 0; $i < $count; $i++) {
                $weeksAgo = fake()->numberBetween(0, 4);
                $week = min(12, max(1, 13 - $weeksAgo));
                $submissionDate = Carbon::now()->subWeeks($weeksAgo);
                $statusChoice = random_int(1, 4) === 1 ? 'Approved' : 'Submitted';

                $worklog = Worklog::create([
                    'student_id'     => $student->id,
                    'week_number'    => $week,
                    'description'    => fake()->realText(220),
                    'challenges'     => fake()->optional(0.7)->realText(160),
                    'submission_date' => $submissionDate->toDateString(),
                    'status'         => $statusChoice,
                    'feedback'       => fake()->optional()->realText(180),
                    'reviewer_id'    => $statusChoice === 'Approved' ? $tutor->id : null,
                    'reviewed_at'    => $statusChoice === 'Approved' ? $submissionDate->copy()->addDays(rand(1, 4)) : null,
                ]);

                if ($statusChoice === 'Approved') {
                    WorklogReview::create([
                        'worklog_id'   => $worklog->id,
                        'tutor_id'     => $tutor->id,
                        'feedback'     => fake()->realText(180),
                        'status'       => 'Approved',
                        'reviewed_at' => $worklog->reviewed_at,
                    ]);
                }

                if (fake()->boolean(60)) {
                    Attachment::create([
                        'worklog_id' => $worklog->id,
                        'file_path'  => 'attachments/' . fake()->uuid() . '.pdf',
                        'file_type'  => fake()->randomElement([
                            'application/pdf',
                            'image/png',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ]),
                        'file_size'  => fake()->numberBetween(20480, 5242880),
                    ]);
                }
            }
        }
    }

    private function seedFollowups($students, User $tutor): void
    {
        $dueSoon = $this->desiredDueSoonFollowups;

        foreach ($students as $student) {
            $count = min(3, max(0, fake()->numberBetween(0, 3)));

            for ($i = 0; $i < $count; $i++) {
                $isDueSoon = $dueSoon > 0 && fake()->boolean(35);
                if ($isDueSoon) {
                    $dueSoon--;
                }

                $scheduledAt = $isDueSoon
                    ? Carbon::now()->addDays(rand(1, 6))->addHours(rand(8, 18))
                    : Carbon::now()->addDays(rand(3, 40))->addHours(rand(8, 18));

                $status = $scheduledAt->isPast() ? fake()->randomElement(['Completed', 'Cancelled']) : fake()->randomElement(['Scheduled', 'Completed']);

                Followup::create([
                    'student_id'   => $student->id,
                    'tutor_id'     => $tutor->id,
                    'type'         => fake()->randomElement(['Online', 'Offline', 'Phone Call', 'Office Meeting']),
                    'scheduled_at' => $scheduledAt,
                    'notes'        => fake()->optional(0.5)->sentence(),
                    'status'       => $status,
                ]);
            }
        }

        if ($dueSoon > 0) {
            $fallback = $students->shuffle()->take($dueSoon);

            foreach ($fallback as $index => $student) {
                Followup::create([
                    'student_id'   => $student->id,
                    'tutor_id'     => $tutor->id,
                    'type'         => 'Office Meeting',
                    'scheduled_at' => Carbon::now()->addDays($index + 1)->addHours(10),
                    'notes'        => 'Weekly follow-up',
                    'status'       => 'Scheduled',
                ]);
            }
        }
    }

    private function seedIssues($students, User $tutor): void
    {
        if ($students->isEmpty()) {
            return;
        }

        $openIssues = $this->desiredOpenIssues;

        for ($i = 0; $i < 8; $i++) {
            $student = $students->random();
            $status = $i < $openIssues ? fake()->randomElement(['Open', 'Open', 'In Progress']) : fake()->randomElement(['In Progress', 'Resolved', 'Closed']);

            Issue::create([
                'student_id'  => $student->id,
                'tutor_id'    => $tutor->id,
                'title'       => fake()->sentence(6),
                'description' => fake()->realText(240),
                'status'      => $status,
                'priority'    => $i < 2 ? 'High' : fake()->randomElement(['Low', 'Medium', 'Medium', 'High', 'Critical']),
            ]);
        }

        $remaining = $openIssues - Issue::where('tutor_id', $tutor->id)->whereIn('status', ['Open', 'In Progress'])->count();

        if ($remaining > 0) {
            for ($i = 0; $i < $remaining; $i++) {
                Issue::create([
                    'student_id'  => $students->random()->id,
                    'tutor_id'    => $tutor->id,
                    'title'       => fake()->sentence(6),
                    'description' => fake()->realText(240),
                    'status'      => fake()->randomElement(['Open', 'In Progress']),
                    'priority'    => 'High',
                ]);
            }
        }
    }
}
