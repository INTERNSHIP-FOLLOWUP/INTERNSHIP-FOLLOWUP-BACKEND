<?php

namespace Tests\Feature;

use App\Imports\StudentImport;
use App\Models\Batch;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class StudentImportTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $tutorUser;
    private Role $studentRole;
    private Batch $batch;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'tutor']);
        $this->studentRole = Role::create(['name' => 'student']);
        Role::create(['name' => 'supervisor']);

        $adminRole = Role::where('name', 'admin')->first();
        $tutorRole = Role::where('name', 'tutor')->first();

        $this->adminUser = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'role_id' => $adminRole->id,
        ]);

        $this->tutorUser = User::create([
            'first_name' => 'Tutor',
            'last_name' => 'User',
            'email' => 'tutor@test.com',
            'password' => Hash::make('password'),
            'role_id' => $tutorRole->id,
        ]);

        $this->batch = Batch::create([
            'batch_name' => 'PNC2026',
            'year' => '2026',
        ]);
    }

    /** @test */
    public function student_import_creates_user_and_student_records_with_sequential_student_code()
    {
        $import = new StudentImport();
        $import->model([
            'first_name' => 'Sok',
            'last_name' => 'Dara',
            'email' => 'sok.dara@example.com',
            'phone' => '012345678',
            'gender' => 'Male',
            'batches' => 'PNC2026',
        ]);

        $this->assertEquals(1, $import->getImportedCount());
        $this->assertCount(0, $import->getErrors());

        $this->assertDatabaseHas('users', [
            'email' => 'sok.dara@example.com',
            'first_name' => 'Sok',
            'last_name' => 'Dara',
            'phone' => '012345678',
            'theme' => 'light',
            'role_id' => $this->studentRole->id,
        ]);

        $user = User::where('email', 'sok.dara@example.com')->first();
        $this->assertTrue(Hash::check('12345678', $user->password));

        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'student_code' => 'STU001',
            'batch_id' => $this->batch->id,
            'gender' => 'Male',
            'status' => 'active',
        ]);
    }

    /** @test */
    public function student_import_generates_sequential_codes()
    {
        // Import first student
        $import1 = new StudentImport();
        $import1->model([
            'first_name' => 'Sok',
            'last_name' => 'Dara',
            'email' => 'sok.dara@example.com',
            'gender' => 'Male',
            'batches' => 'PNC2026',
        ]);

        // Import second student
        $import2 = new StudentImport();
        $import2->model([
            'first_name' => 'Channy',
            'last_name' => 'Ven',
            'email' => 'channy.ven@example.com',
            'gender' => 'Female',
            'batches' => 'PNC2026',
        ]);

        $this->assertEquals(1, $import1->getImportedCount());
        $this->assertEquals(1, $import2->getImportedCount());

        $student1 = Student::where('email', 'sok.dara@example.com')->first();
        $student2 = Student::where('email', 'channy.ven@example.com')->first();

        $this->assertEquals('STU001', $student1->student_code);
        $this->assertEquals('STU002', $student2->student_code);
    }

    /** @test */
    public function student_import_skips_duplicate_emails()
    {
        User::create([
            'first_name' => 'Existing',
            'last_name' => 'User',
            'email' => 'existing@example.com',
            'password' => Hash::make('12345678'),
            'role_id' => $this->studentRole->id,
        ]);

        $import = new StudentImport();
        $import->model([
            'first_name' => 'Duplicate',
            'last_name' => 'Email',
            'email' => 'existing@example.com',
            'gender' => 'Male',
            'batches' => 'PNC2026',
        ]);

        $this->assertEquals(0, $import->getImportedCount());
        $this->assertCount(1, $import->getErrors());
        $this->assertStringContainsString("Email 'existing@example.com' already exists", $import->getErrors()[0]['reason']);
    }

    /** @test */
    public function student_import_skips_invalid_batch_name()
    {
        $import = new StudentImport();
        $import->model([
            'first_name' => 'InvalidBatch',
            'last_name' => 'Student',
            'email' => 'invalid.batch@example.com',
            'gender' => 'Male',
            'batches' => 'NONEXISTENT2026',
        ]);

        $this->assertEquals(0, $import->getImportedCount());
        $this->assertCount(1, $import->getErrors());
        $this->assertStringContainsString("Batch 'NONEXISTENT2026' not found", $import->getErrors()[0]['reason']);
    }

    /** @test */
    public function student_import_skips_missing_required_fields()
    {
        $import = new StudentImport();
        $import->model([
            'first_name' => '',
            'last_name' => 'NoFirst',
            'email' => 'nofirst@example.com',
            'gender' => 'Male',
            'batches' => 'PNC2026',
        ]);

        $this->assertEquals(0, $import->getImportedCount());
        $this->assertCount(1, $import->getErrors());
        $this->assertStringContainsString('required fields', $import->getErrors()[0]['reason']);
    }

    /** @test */
    public function student_import_validates_email_format()
    {
        $import = new StudentImport();
        $import->model([
            'first_name' => 'Invalid',
            'last_name' => 'Email',
            'email' => 'invalid-email-format',
            'gender' => 'Male',
            'batches' => 'PNC2026',
        ]);

        $this->assertEquals(0, $import->getImportedCount());
        $this->assertCount(1, $import->getErrors());
        $this->assertStringContainsString('not valid', $import->getErrors()[0]['reason']);
    }

    /** @test */
    public function student_import_password_is_correctly_hashed()
    {
        $import = new StudentImport();
        $import->model([
            'first_name' => 'Password',
            'last_name' => 'Test',
            'email' => 'password@example.com',
            'gender' => 'Male',
            'batches' => 'PNC2026',
        ]);

        $user = User::where('email', 'password@example.com')->first();
        $this->assertTrue(Hash::check('12345678', $user->password));
    }

    /** @test */
    public function non_admin_cannot_access_import_endpoints()
    {
        $token = $this->tutorUser->createToken('tutor-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/admin/students/import');

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_download_import_template()
    {
        $token = $this->adminUser->createToken('admin-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/admin/students/import/template');

        $response->assertOk();
    }

    /** @test */
    public function student_import_links_user_student_and_batch_relationships()
    {
        $import = new StudentImport();
        $import->model([
            'first_name' => 'Borey',
            'last_name' => 'Peni',
            'email' => 'borey.peni@pn-internship.edu.kh',
            'phone' => '070 125 859',
            'gender' => 'Male',
            'batches' => 'PNC2026',
        ]);

        $this->assertEquals(1, $import->getImportedCount());

        $this->assertDatabaseHas('users', [
            'email' => 'borey.peni@pn-internship.edu.kh',
            'first_name' => 'Borey',
            'last_name' => 'Peni',
        ]);

        $user = User::where('email', 'borey.peni@pn-internship.edu.kh')->first();

        $this->assertDatabaseHas('students', [
            'user_id' => $user->id,
            'batch_id' => $this->batch->id,
            'gender' => 'Male',
            'status' => 'active',
        ]);

        $this->assertEquals($this->batch->id, $user->studentProfile->batch_id);
        $this->assertEquals($user->id, $this->batch->students->first()->user_id);
    }

    /** @test */
    public function student_import_supports_different_batch_column_names()
    {
        // Test with 'batch' column
        $import1 = new StudentImport();
        $import1->model([
            'first_name' => 'Test1',
            'last_name' => 'User',
            'email' => 'test1@example.com',
            'gender' => 'Male',
            'batch' => 'PNC2026',
        ]);

        $this->assertEquals(1, $import1->getImportedCount());

        // Test with 'batch_name' column
        $import2 = new StudentImport();
        $import2->model([
            'first_name' => 'Test2',
            'last_name' => 'User',
            'email' => 'test2@example.com',
            'gender' => 'Female',
            'batch_name' => 'PNC2026',
        ]);

        $this->assertEquals(1, $import2->getImportedCount());
    }
}
