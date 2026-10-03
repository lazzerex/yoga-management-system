<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\TeacherAttendance;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachProfileTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_admin_can_create_a_coach_profile(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $classType = ClassType::factory()->create();

        $this->actingAs($this->admin())
            ->post('/cms/operations/coaches', [
                'user_id' => $coach->id,
                'years_experience' => 5,
                'class_type_ids' => [$classType->id],
            ])
            ->assertRedirect('/cms/operations/coaches');

        $this->assertDatabaseHas('coach_profiles', ['user_id' => $coach->id, 'years_experience' => 5]);
        $this->assertDatabaseHas('coach_class_type', ['class_type_id' => $classType->id]);
    }

    public function test_creating_a_coach_profile_for_a_non_coach_user_fails(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($this->admin())
            ->from('/cms/operations/coaches/create')
            ->post('/cms/operations/coaches', [
                'user_id' => $member->id,
                'years_experience' => 3,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('coach_profiles', ['user_id' => $member->id]);
    }

    public function test_a_user_cannot_have_two_coach_profiles(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $coach->id]);

        $this->actingAs($this->admin())
            ->from('/cms/operations/coaches/create')
            ->post('/cms/operations/coaches', [
                'user_id' => $coach->id,
                'years_experience' => 2,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertSame(1, CoachProfile::where('user_id', $coach->id)->count());
    }

    public function test_admin_can_update_a_coach_profile(): void
    {
        $profile = CoachProfile::factory()->create(['years_experience' => 2]);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/coaches/{$profile->id}", [
                'years_experience' => 9,
                'is_active' => false,
            ])
            ->assertRedirect('/cms/operations/coaches');

        $profile->refresh();
        $this->assertSame(9, $profile->years_experience);
        $this->assertFalse($profile->is_active);
    }

    public function test_admin_can_delete_a_coach_profile(): void
    {
        $profile = CoachProfile::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/cms/operations/coaches/{$profile->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('coach_profiles', ['id' => $profile->id]);
    }

    public function test_a_coach_profile_assigned_to_a_session_cannot_be_deleted(): void
    {
        $session = ClassSession::factory()->create();

        $this->actingAs($this->admin())
            ->from('/cms/operations/coaches')
            ->delete("/cms/operations/coaches/{$session->coach_profile_id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('coach_profiles', ['id' => $session->coach_profile_id]);
    }

    public function test_a_coach_profile_with_only_attendance_history_cannot_be_deleted(): void
    {
        $profile = CoachProfile::factory()->create();
        TeacherAttendance::factory()->create(['coach_profile_id' => $profile->id]);

        $this->actingAs($this->admin())
            ->from('/cms/operations/coaches')
            ->delete("/cms/operations/coaches/{$profile->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('coach_profiles', ['id' => $profile->id]);
    }

    public function test_coach_cannot_view_or_manage_the_coach_directory(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create();

        $this->actingAs($coach)->get('/cms/operations/coaches')->assertForbidden();
        $this->actingAs($coach)->get('/cms/operations/coaches/create')->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
