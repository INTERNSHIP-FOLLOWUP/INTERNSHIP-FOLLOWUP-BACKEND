<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        // Admin notifications
        $admin = $users->where('role_id', 1)->first();
        if ($admin) {
            Notification::factory()
                ->forUser($admin->id)
                ->unread()
                ->ofType('system')
                ->ofPriority('high')
                ->create([
                    'title' => 'New Internship Assignment',
                    'message' => 'John Doe has been assigned to Tech Company Inc. for the Summer 2026 internship program.',
                ]);

            Notification::factory()
                ->forUser($admin->id)
                ->unread()
                ->ofType('validation')
                ->ofPriority('urgent')
                ->create([
                    'title' => 'Internship Validation Pending',
                    'message' => 'Tech Company Inc. has submitted an internship validation for Jane Smith. Review required.',
                ]);
        }

        // Tutor notifications
        $tutor = $users->where('role_id', 2)->first();
        if ($tutor) {
            Notification::factory()
                ->forUser($tutor->id)
                ->unread()
                ->ofType('worklog')
                ->ofPriority('normal')
                ->create([
                    'title' => 'New Worklog Submitted',
                    'message' => 'John Doe submitted Week 5 Worklog. Please review and provide feedback.',
                ]);

            Notification::factory()
                ->forUser($tutor->id)
                ->read()
                ->ofType('followup')
                ->ofPriority('normal')
                ->create([
                    'title' => 'Follow-up Scheduled',
                    'message' => 'A follow-up meeting has been scheduled for John Doe on July 28, 2026.',
                ]);
        }

        // Company representative notifications
        $company = $users->where('role_id', 3)->first();
        if ($company) {
            Notification::factory()
                ->forUser($company->id)
                ->unread()
                ->ofType('assignment')
                ->ofPriority('normal')
                ->create([
                    'title' => 'New Internship Assignment',
                    'message' => 'You have been assigned a new student, John Doe, for the Summer 2026 internship.',
                    'action_url' => '/assignments/5',
                ]);

            Notification::factory()
                ->forUser($company->id)
                ->read()
                ->ofType('reminder')
                ->ofPriority('high')
                ->create([
                    'title' => 'Evaluation Due',
                    'message' => 'The mid-term evaluation for John Doe is due in 3 days.',
                    'action_url' => '/evaluations/create',
                ]);
        }

        // Student notifications
        $student = $users->where('role_id', 4)->first();
        if ($student) {
            Notification::factory()
                ->forUser($student->id)
                ->unread()
                ->ofType('general')
                ->ofPriority('normal')
                ->create([
                    'title' => 'Worklog Reviewed',
                    'message' => 'Your Week 4 Worklog has been reviewed by your tutor.',
                    'action_url' => '/worklogs/4',
                ]);

            Notification::factory()
                ->forUser($student->id)
                ->read()
                ->ofType('reminder')
                ->ofPriority('low')
                ->create([
                    'title' => 'Weekly Worklog Reminder',
                    'message' => 'Don\'t forget to submit your Week 6 Worklog before the deadline.',
                    'action_url' => '/worklogs/create',
                ]);
        }

        // Additional general notifications for all users
        $users->each(function ($user) {
            Notification::factory()
                ->forUser($user->id)
                ->unread()
                ->ofType('system')
                ->ofPriority('normal')
                ->create([
                    'title' => 'System Maintenance Scheduled',
                    'message' => 'The system will undergo maintenance on July 30, 2026, from 2:00 AM to 4:00 AM UTC.',
                ]);
        });
    }
}