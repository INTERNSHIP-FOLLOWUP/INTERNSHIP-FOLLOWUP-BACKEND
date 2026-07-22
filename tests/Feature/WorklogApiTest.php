<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use App\Models\Worklog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorklogApiTest extends TestCase
{
    use RefreshDatabase;

    private User $studentUser;
    private Student $student;
    private User $tutorUser;
    private string $studentToken;
    private string $tutorToken;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create roles
        Role::create(['name' => 'student']);
        Role::create(['name' => 'tutor']);
        Role::create(['name' => 'admin']);

        $studentRole = Role::where('name', 'student')->first();
        $tutorRole = Role::where('name', 'tutor')->first();
        $batch = Batch::create(['batch_name' => 'Test Batch', 'year' => '2026']);

        // Create student user
        $this->studentUser = User::create([
            'first_name' => 'Test',
            'last_name' => 'Student',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role_id' => $studentRole->id,
        ]);

        // Create Student record
        $this->student = Student::create([
            'student_code' => 'STU001',
            'batch_id' => $batch->id,
            'name' => 'Test Student',
            'gender' => 'Male',
            'email' => 'student@test.com',
            'status' => 'Active',
        ]);

        // Create tutor user
        $this->tutorUser = User::create([
            'first_name' => 'Test',
            'last_name' => 'Tutor',
            'email' => 'tutor@test.com',
            'password' => bcrypt('password'),
            'role_id' => $tutorRole->id,
        ]);

        $this->studentToken = $this->studentUser->createToken('test-token')->plainTextToken;
        $this->tutorToken = $this->tutorUser->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function student_can_create_a_worklog()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson('/api/worklogs', [
                'week_number' => 1,
                'description' => 'Week 1 progress report',
                'challenges' => 'Had some issues with environment setup',
                'submission_date' => now()->toDateString(),
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => ['id', 'week_number', 'description', 'challenges', 'submission_date', 'status', 'attachments'],
                'message',
            ]);

        $this->assertDatabaseHas('worklogs', [
            'student_id' => $this->student->id,
            'week_number' => 1,
            'status' => 'Draft',
        ]);
    }

    /** @test */
    public function student_can_create_worklog_with_attachments()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson('/api/worklogs', [
                'week_number' => 2,
                'description' => 'Week 2 report',
                'submission_date' => now()->toDateString(),
                'attachments' => [$file],
            ]);

        $response->assertStatus(201);

        $worklogId = $response->json('data.id');
        $this->assertDatabaseHas('attachments', [
            'worklog_id' => $worklogId,
        ]);
    }

    /** @test */
    public function student_can_list_their_own_worklogs()
    {
        Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'My worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/worklogs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'meta' => ['total', 'per_page', 'current_page', 'last_page'],
            ]);

        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function student_cannot_see_other_students_worklogs()
    {
        // Create another student
        $otherStudent = Student::create([
            'student_code' => 'STU002',
            'name' => 'Other Student',
            'gender' => 'Female',
            'email' => 'other@test.com',
            'status' => 'Active',
        ]);

        Worklog::create([
            'student_id' => $otherStudent->id,
            'week_number' => 1,
            'description' => 'Other student worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/worklogs');

        $this->assertCount(0, $response->json('data'));
    }

    /** @test */
    public function tutor_can_see_assigned_students_worklogs()
    {
        // Assign student to tutor
        $this->student->update(['tutor_id' => $this->tutorUser->id]);

        Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Student worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->getJson('/api/worklogs');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function student_can_show_their_own_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'My worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson("/api/worklogs/{$worklog->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $worklog->id);
    }

    /** @test */
    public function student_can_update_their_draft_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Original description',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->putJson("/api/worklogs/{$worklog->id}", [
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('worklogs', [
            'id' => $worklog->id,
            'description' => 'Updated description',
        ]);
    }

    /** @test */
    public function student_cannot_update_submitted_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->putJson("/api/worklogs/{$worklog->id}", [
                'description' => 'Trying to update',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function student_can_delete_their_draft_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'To be deleted',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->deleteJson("/api/worklogs/{$worklog->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('worklogs', ['id' => $worklog->id]);
    }

    /** @test */
    public function student_cannot_delete_submitted_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->deleteJson("/api/worklogs/{$worklog->id}");

        $response->assertStatus(422);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_worklogs()
    {
        $response = $this->getJson('/api/worklogs');
        $response->assertStatus(401);
    }

    /** @test */
    public function worklog_validation_fails_with_invalid_data()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->postJson('/api/worklogs', [
                'week_number' => 100,
                'description' => '',
                'submission_date' => 'invalid-date',
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    /** @test */
    public function deleting_worklog_also_deletes_attachments()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'With attachments',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        Attachment::create([
            'worklog_id' => $worklog->id,
            'file_path' => 'worklogs/test.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1024,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->deleteJson("/api/worklogs/{$worklog->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('attachments', ['worklog_id' => $worklog->id]);
    }

    /** @test */
    public function tutor_can_approve_submitted_worklog()
    {
        // Assign student to tutor
        $this->student->update(['tutor_id' => $this->tutorUser->id]);

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->putJson("/api/worklogs/{$worklog->id}/status", [
                'status' => 'Approved',
                'feedback' => 'Great work! Keep it up.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved')
            ->assertJsonPath('data.feedback', 'Great work! Keep it up.');

        $this->assertDatabaseHas('worklogs', [
            'id' => $worklog->id,
            'status' => 'Approved',
            'feedback' => 'Great work! Keep it up.',
        ]);
    }

    /** @test */
    public function tutor_can_reject_submitted_worklog()
    {
        // Assign student to tutor
        $this->student->update(['tutor_id' => $this->tutorUser->id]);

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->putJson("/api/worklogs/{$worklog->id}/status", [
                'status' => 'Rejected',
                'feedback' => 'Please provide more details about your implementation.',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Rejected')
            ->assertJsonPath('data.feedback', 'Please provide more details about your implementation.');

        $this->assertDatabaseHas('worklogs', [
            'id' => $worklog->id,
            'status' => 'Rejected',
            'feedback' => 'Please provide more details about your implementation.',
        ]);
    }

    /** @test */
    public function tutor_cannot_approve_draft_worklog()
    {
        // Assign student to tutor
        $this->student->update(['tutor_id' => $this->tutorUser->id]);

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Draft worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->putJson("/api/worklogs/{$worklog->id}/status", [
                'status' => 'Approved',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function tutor_cannot_approve_unassigned_student_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->putJson("/api/worklogs/{$worklog->id}/status", [
                'status' => 'Approved',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function student_cannot_update_worklog_status()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->putJson("/api/worklogs/{$worklog->id}/status", [
                'status' => 'Approved',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function status_validation_fails_with_invalid_status()
    {
        // Assign student to tutor
        $this->student->update(['tutor_id' => $this->tutorUser->id]);

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->putJson("/api/worklogs/{$worklog->id}/status", [
                'status' => 'InvalidStatus',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function tutor_can_submit_feedback_when_rejecting()
    {
        $this->student->update(['tutor_id' => $this->tutorUser->id]);

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->putJson("/api/worklogs/{$worklog->id}/status", [
                'status' => 'Rejected',
                'feedback' => 'Your report lacks sufficient detail. Please expand on your technical approach.',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('worklogs', [
            'id' => $worklog->id,
            'status' => 'Rejected',
            'feedback' => 'Your report lacks sufficient detail. Please expand on your technical approach.',
        ]);
    }

    /** @test */
    public function feedback_is_optional_in_status_update()
    {
        $this->student->update(['tutor_id' => $this->tutorUser->id]);

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        // Approve without feedback
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->putJson("/api/worklogs/{$worklog->id}/status", [
                'status' => 'Approved',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.feedback', null);

        $this->assertDatabaseHas('worklogs', [
            'id' => $worklog->id,
            'status' => 'Approved',
            'feedback' => null,
        ]);
    }

    // ─── Resubmit Flow: Student can revise & resubmit rejected worklogs ───────

    /** @test */
    public function student_can_update_rejected_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Rejected worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Rejected',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->putJson("/api/worklogs/{$worklog->id}", [
                'description' => 'Revised after feedback',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('worklogs', [
            'id' => $worklog->id,
            'description' => 'Revised after feedback',
        ]);
    }

    /** @test */
    public function student_can_resubmit_rejected_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Rejected worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Rejected',
        ]);

        // Student revises and resubmits (Rejected -> Submitted)
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->putJson("/api/worklogs/{$worklog->id}", [
                'description' => 'Revised and resubmitted',
                'challenges' => 'Addressed all feedback from tutor',
                'status' => 'Submitted',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Submitted');

        $this->assertDatabaseHas('worklogs', [
            'id' => $worklog->id,
            'description' => 'Revised and resubmitted',
            'status' => 'Submitted',
        ]);
    }

    /** @test */
    public function student_can_delete_rejected_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Rejected worklog to delete',
            'submission_date' => now()->toDateString(),
            'status' => 'Rejected',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->deleteJson("/api/worklogs/{$worklog->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('worklogs', ['id' => $worklog->id]);
    }

    /** @test */
    public function student_cannot_update_approved_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Approved worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->putJson("/api/worklogs/{$worklog->id}", [
                'description' => 'Trying to update',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function student_cannot_delete_approved_worklog()
    {
        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Approved worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->deleteJson("/api/worklogs/{$worklog->id}");

        $response->assertStatus(422);
    }

    // ─── Admin Worklog Routes ─────────────────────────────────────────────────

    private User $adminUser;
    private string $adminToken;

    private function setUpAdmin(): void
    {
        if (isset($this->adminUser)) {
            return;
        }

        $adminRole = Role::where('name', 'admin')->first();

        $this->adminUser = User::create([
            'first_name' => 'Test',
            'last_name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role_id' => $adminRole->id,
        ]);

        $this->adminToken = $this->adminUser->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function admin_can_list_all_worklogs()
    {
        $this->setUpAdmin();

        Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Worklog 1',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 2,
            'description' => 'Worklog 2',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/worklogs');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function admin_can_create_worklog_for_any_student()
    {
        $this->setUpAdmin();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/worklogs', [
                'student_id' => $this->student->id,
                'week_number' => 1,
                'description' => 'Admin created worklog',
                'submission_date' => now()->toDateString(),
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('worklogs', [
            'student_id' => $this->student->id,
            'description' => 'Admin created worklog',
        ]);
    }

    /** @test */
    public function admin_can_show_any_worklog()
    {
        $this->setUpAdmin();

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Any worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Draft',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson("/api/admin/worklogs/{$worklog->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $worklog->id);
    }

    /** @test */
    public function admin_can_update_any_worklog()
    {
        $this->setUpAdmin();

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Old description',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson("/api/admin/worklogs/{$worklog->id}", [
                'description' => 'Admin updated description',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('worklogs', [
            'id' => $worklog->id,
            'description' => 'Admin updated description',
        ]);
    }

    /** @test */
    public function admin_can_delete_any_worklog()
    {
        $this->setUpAdmin();

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'To be deleted by admin',
            'submission_date' => now()->toDateString(),
            'status' => 'Approved',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson("/api/admin/worklogs/{$worklog->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('worklogs', ['id' => $worklog->id]);
    }

    /** @test */
    public function admin_can_update_worklog_status()
    {
        $this->setUpAdmin();

        $worklog = Worklog::create([
            'student_id' => $this->student->id,
            'week_number' => 1,
            'description' => 'Submitted worklog',
            'submission_date' => now()->toDateString(),
            'status' => 'Submitted',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson("/api/admin/worklogs/{$worklog->id}/status", [
                'status' => 'Approved',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Approved');
    }

    /** @test */
    public function non_admin_cannot_access_admin_worklogs()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->studentToken)
            ->getJson('/api/admin/worklogs');

        $response->assertStatus(403);
    }
}
