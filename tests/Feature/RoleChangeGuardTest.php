<?php

namespace Tests\Feature;

use App\Models\ClassSchedule;
use App\Models\CoachProfile;
use App\Models\Enrollment;
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

    public function test_a_coach_with_schedules_cannot_be_changed_to_member(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);
        ClassSchedule::factory()->create(['coach_profile_id' => $profile->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$coach->id}/role", ['role' => 'member'])
            ->assertSessionHasErrors('role');

        $this->assertSame('coach', $coach->fresh()->role);
        $this->assertNotNull($coach->fresh()->coachProfile);
    }

    public function test_a_member_with_bookings_cannot_be_changed_to_coach(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $profile = StudentProfile::factory()->create(['user_id' => $member->id]);
        Enrollment::factory()->create(['student_profile_id' => $profile->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$member->id}/role", ['role' => 'coach'])
            ->assertSessionHasErrors('role');

        $this->assertSame('member', $member->fresh()->role);
    }

    public function test_a_coach_with_no_history_becomes_a_member_with_a_swapped_profile(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$coach->id}/role", ['role' => 'member'])
            ->assertSessionHasNoErrors();

        $user = $coach->fresh();
        $this->assertSame('member', $user->role);
        $this->assertNull($user->coachProfile);
        $this->assertNotNull($user->studentProfile);
    }

    public function test_the_edit_form_swaps_an_unused_member_profile_for_a_coach_profile(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        StudentProfile::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$member->id}", $this->details($member, 'coach'))
            ->assertSessionHasNoErrors();

        $user = $member->fresh();
        $this->assertSame('coach', $user->role);
        $this->assertNull($user->studentProfile);
        $this->assertNotNull($user->coachProfile);
    }

    public function test_the_edit_form_blocks_a_coach_with_lesson_history(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);
        ClassSchedule::factory()->create(['coach_profile_id' => $profile->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$coach->id}", $this->details($coach, 'member'))
            ->assertSessionHasErrors('role');

        $this->assertSame('coach', $coach->fresh()->role);
    }

    public function test_a_coach_promoted_to_admin_loses_the_empty_coach_profile(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$coach->id}", $this->details($coach, 'admin'))
            ->assertSessionHasNoErrors();

        $this->assertNull($coach->fresh()->coachProfile);
        $this->assertNull($coach->fresh()->studentProfile);
    }

    public function test_the_edit_form_still_saves_details_when_the_role_is_unchanged(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $coach->id]);
        ClassSchedule::factory()->create(['coach_profile_id' => $profile->id]);

        $this->actingAs($this->admin())
            ->patch("/cms/admin/users/{$coach->id}", ['name' => 'Renamed Coach'] + $this->details($coach, 'coach'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Renamed Coach', $coach->fresh()->name);
        $this->assertSame($profile->id, $coach->fresh()->coachProfile->id);
    }

    private function details(User $user, string $role): array
    {
        return [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'role' => $role,
        ];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
