<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $tutorUser;
    private string $adminToken;
    private string $tutorToken;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'tutor']);
        Role::create(['name' => 'student']);
        Role::create(['name' => 'company']);

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

        $this->adminToken = $this->adminUser->createToken('test-token')->plainTextToken;
        $this->tutorToken = $this->tutorUser->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function admin_can_list_users()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/users');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'email', 'role']],
                'meta' => ['current_page', 'last_page', 'total'],
                'counts' => ['admin', 'tutor', 'student', 'company'],
            ]);
    }

    /** @test */
    public function non_admin_cannot_list_users()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->tutorToken)
            ->getJson('/api/admin/users');

        $response->assertForbidden();
    }

    /** @test */
    public function unauthenticated_user_cannot_list_users()
    {
        $response = $this->getJson('/api/admin/users');

        $response->assertUnauthorized();
    }

    /** @test */
    public function admin_can_filter_users_by_role()
    {
        User::create([
            'first_name' => 'Student',
            'last_name' => 'User',
            'email' => 'student@test.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'student')->first()->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/users?role=student');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Student User', $response->json('data.0.name'));
    }

    /** @test */
    public function admin_can_search_users_by_name_and_email()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/users?search=Admin');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
    }

    /** @test */
    public function admin_can_create_a_user()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/users', [
                'first_name' => 'New',
                'last_name' => 'User',
                'email' => 'newuser@test.com',
                'password' => 'password123',
                'role' => 'student',
            ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'New User')
            ->assertJsonPath('email', 'newuser@test.com');

        $this->assertDatabaseHas('users', ['email' => 'newuser@test.com']);
    }

    /** @test */
    public function admin_cannot_create_user_with_duplicate_email()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/users', [
                'first_name' => 'Duplicate',
                'last_name' => '',
                'email' => 'admin@test.com',
                'password' => 'password123',
                'role' => 'student',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function admin_can_view_a_user()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/users/' . $this->tutorUser->id);

        $response->assertOk()
            ->assertJsonPath('name', 'Tutor User')
            ->assertJsonPath('email', 'tutor@test.com');
    }

    /** @test */
    public function admin_can_update_a_user()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/admin/users/' . $this->tutorUser->id, [
                'first_name' => 'Updated',
                'last_name' => 'Tutor',
                'role' => 'student',
            ]);

        $response->assertOk()
            ->assertJsonPath('user.name', 'Updated Tutor');

        $this->assertDatabaseHas('users', [
            'id' => $this->tutorUser->id,
            'first_name' => 'Updated',
            'last_name' => 'Tutor',
            'role_id' => Role::where('name', 'student')->first()->id,
        ]);
    }

    /** @test */
    public function admin_can_deactivate_a_user()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/admin/users/' . $this->tutorUser->id . '/deactivate');

        $response->assertOk()
            ->assertJsonPath('message', 'User deactivated successfully.');

        $this->assertSoftDeleted('users', ['id' => $this->tutorUser->id]);
    }

    /** @test */
    public function admin_cannot_deactivate_themselves()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/admin/users/' . $this->adminUser->id . '/deactivate');

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_activate_a_deactivated_user()
    {
        $this->tutorUser->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->putJson('/api/admin/users/' . $this->tutorUser->id . '/activate');

        $response->assertOk()
            ->assertJsonPath('message', 'User activated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $this->tutorUser->id,
            'deleted_at' => null,
        ]);
    }

    /** @test */
    public function admin_can_reset_user_password()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->postJson('/api/admin/users/' . $this->tutorUser->id . '/reset-password', [
                'password' => 'newpassword123',
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Password reset successfully. All existing tokens have been revoked.');

        $this->assertTrue(
            Hash::check('newpassword123', $this->tutorUser->fresh()->password)
        );
    }

    /** @test */
    public function admin_can_delete_a_user()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson('/api/admin/users/' . $this->tutorUser->id);

        $response->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $this->tutorUser->id]);
    }

    /** @test */
    public function admin_cannot_delete_themselves()
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->deleteJson('/api/admin/users/' . $this->adminUser->id);

        $response->assertForbidden();
    }

    /** @test */
    public function admin_can_filter_deactivated_users()
    {
        $this->tutorUser->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->adminToken)
            ->getJson('/api/admin/users?status=deactivated');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
