<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CoachProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_user_with_a_coach_profile_cannot_be_deleted(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $this->actingAs($this->admin())
            ->from('/cms/admin/users')
            ->delete("/cms/admin/users/{$coach->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('users', ['id' => $coach->id]);
    }

    public function test_a_user_with_a_student_profile_cannot_be_deleted(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        StudentProfile::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->admin())
            ->from('/cms/admin/users')
            ->delete("/cms/admin/users/{$member->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('users', ['id' => $member->id]);
    }

    public function test_a_blocked_delete_does_not_write_an_audit_entry(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        StudentProfile::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->admin())
            ->from('/cms/admin/users')
            ->delete("/cms/admin/users/{$member->id}");

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_an_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->from('/cms/admin/users')
            ->delete("/cms/admin/users/{$admin->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $admin = $this->admin();

        // Only a non-admin with the manage permission can reach this guard.
        $manager = User::factory()->create(['role' => 'coach']);
        $manager->givePermissionTo(['admin.users.view', 'admin.users.manage']);

        $this->actingAs($manager)
            ->from('/cms/admin/users')
            ->delete("/cms/admin/users/{$admin->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_a_user_without_a_profile_is_deleted_and_audited(): void
    {
        $admin = $this->admin();
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($admin)
            ->delete("/cms/admin/users/{$member->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $member->id]);

        $log = AuditLog::where('action', 'delete_user')->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->causer_id);
        $this->assertSame('member', $log->meta['role']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
