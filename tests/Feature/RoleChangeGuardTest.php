<?php

namespace Tests\Feature;

use App\Models\CoachProfile;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleChangeGuardTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_role_cannot_be_changed_away_from_coach_while_a_coach_profile_exists(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$coach->id}/role", ['role' => 'member'])
            ->assertSessionHasErrors('role');

        $this->assertSame('coach', $coach->fresh()->role);
    }

    public function test_role_cannot_be_changed_away_from_member_while_a_student_profile_exists(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        StudentProfile::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$member->id}/role", ['role' => 'coach'])
            ->assertSessionHasErrors('role');

        $this->assertSame('member', $member->fresh()->role);
    }

    public function test_role_can_be_changed_once_the_profile_is_removed(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);
        $profile->delete();

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$coach->id}/role", ['role' => 'member'])
            ->assertRedirect();

        $this->assertSame('member', $coach->fresh()->role);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
