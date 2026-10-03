<?php

namespace Tests\Feature;

use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\Enrollment;
use App\Models\StudentProfile;
use App\Models\User;
use App\Support\Menu\MenuRegistry;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_admin_can_create_a_student_profile(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($this->admin())
            ->post('/cms/operations/students', [
                'user_id' => $member->id,
                'goals' => 'Build core strength.',
                'medical_notes' => 'Mild knee sensitivity.',
            ])
            ->assertRedirect('/cms/operations/students');

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $member->id,
            'medical_notes' => 'Mild knee sensitivity.',
        ]);
    }

    public function test_creating_a_student_profile_for_a_non_member_user_fails(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $this->actingAs($this->admin())
            ->from('/cms/operations/students/create')
            ->post('/cms/operations/students', ['user_id' => $coach->id])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('student_profiles', ['user_id' => $coach->id]);
    }

    public function test_a_user_cannot_have_two_student_profiles(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        StudentProfile::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->admin())
            ->from('/cms/operations/students/create')
            ->post('/cms/operations/students', ['user_id' => $member->id])
            ->assertSessionHasErrors('user_id');

        $this->assertSame(1, StudentProfile::where('user_id', $member->id)->count());
    }

    public function test_admin_can_update_a_student_profile(): void
    {
        $profile = StudentProfile::factory()->create(['goals' => 'Old goal']);

        $this->actingAs($this->admin())
            ->patch("/cms/operations/students/{$profile->id}", [
                'goals' => 'New goal',
                'is_active' => false,
            ])
            ->assertRedirect('/cms/operations/students');

        $profile->refresh();
        $this->assertSame('New goal', $profile->goals);
        $this->assertFalse($profile->is_active);
    }

    public function test_admin_can_delete_a_student_profile(): void
    {
        $profile = StudentProfile::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/cms/operations/students/{$profile->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('student_profiles', ['id' => $profile->id]);
    }

    public function test_a_student_profile_with_enrollments_cannot_be_deleted(): void
    {
        $profile = StudentProfile::factory()->create();
        Enrollment::factory()->create(['student_profile_id' => $profile->id]);

        $this->actingAs($this->admin())
            ->from('/cms/operations/students')
            ->delete("/cms/operations/students/{$profile->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('student_profiles', ['id' => $profile->id]);
    }

    public function test_medical_notes_are_never_returned_in_the_index_payload(): void
    {
        StudentProfile::factory()->create(['medical_notes' => 'Very sensitive note']);

        $page = $this->actingAs($this->admin())
            ->get('/cms/operations/students')
            ->viewData('page');

        $json = json_encode($page['props']['profiles']);
        $this->assertStringNotContainsString('Very sensitive note', $json);
        $this->assertArrayNotHasKey('medical_notes', $page['props']['profiles']['data'][0]);
    }

    public function test_admin_without_medical_permission_does_not_see_medical_notes_on_edit(): void
    {
        $profile = StudentProfile::factory()->create(['medical_notes' => 'Very sensitive note']);
        $limitedManager = $this->managerWithoutMedicalAccess();

        $page = $this->actingAs($limitedManager)
            ->get("/cms/operations/students/{$profile->id}/edit")
            ->viewData('page');

        $this->assertNull($page['props']['studentProfile']['medical_notes']);
        $this->assertFalse($page['props']['canViewMedical']);
    }

    public function test_admin_without_medical_permission_cannot_set_medical_notes_on_update(): void
    {
        $profile = StudentProfile::factory()->create(['medical_notes' => 'Original note']);
        $limitedManager = $this->managerWithoutMedicalAccess();

        $this->actingAs($limitedManager)
            ->patch("/cms/operations/students/{$profile->id}", [
                'goals' => 'Updated goal',
                'medical_notes' => 'Attempted override',
            ])
            ->assertRedirect('/cms/operations/students');

        $this->assertSame('Original note', $profile->fresh()->medical_notes);
    }

    public function test_viewing_medical_notes_creates_an_audit_log_entry(): void
    {
        $profile = StudentProfile::factory()->create(['medical_notes' => 'Sensitive note']);

        $this->actingAs($this->admin())
            ->get("/cms/operations/students/{$profile->id}/edit")
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'view_student_medical_notes',
            'subject_id' => $profile->user_id,
        ]);
    }

    public function test_coach_cannot_manage_students(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        $profile = StudentProfile::factory()->create();

        $this->actingAs($coach)->get('/cms/operations/students')->assertOk();
        $this->actingAs($coach)->get('/cms/operations/students/create')->assertForbidden();
        $this->actingAs($coach)->get("/cms/operations/students/{$profile->id}/edit")->assertForbidden();
    }

    public function test_a_coach_only_sees_students_booked_into_their_own_sessions(): void
    {
        [$coach, $coachProfile] = $this->coach();
        $mine = StudentProfile::factory()->create();
        $theirs = StudentProfile::factory()->create();

        Enrollment::factory()->create([
            'student_profile_id' => $mine->id,
            'class_session_id' => ClassSession::factory()->create(['coach_profile_id' => $coachProfile->id])->id,
        ]);
        Enrollment::factory()->create(['student_profile_id' => $theirs->id]);

        $props = $this->actingAs($coach)->get('/cms/operations/students')->viewData('page')['props'];

        $this->assertSame([$mine->id], array_column($props['profiles']['data'], 'id'));
        $this->assertSame(1, $props['stats']['total']);
    }

    public function test_a_coach_does_not_see_students_who_only_cancelled_on_them(): void
    {
        [$coach, $coachProfile] = $this->coach();
        $profile = StudentProfile::factory()->create();

        Enrollment::factory()->cancelled()->create([
            'student_profile_id' => $profile->id,
            'class_session_id' => ClassSession::factory()->create(['coach_profile_id' => $coachProfile->id])->id,
        ]);

        $props = $this->actingAs($coach)->get('/cms/operations/students')->viewData('page')['props'];

        $this->assertSame([], $props['profiles']['data']);
        $this->assertSame(0, $props['stats']['total']);
    }

    public function test_each_role_gets_one_student_directory_entry_in_its_own_menu_group(): void
    {
        [$coach] = $this->coach();

        $this->assertSame(
            ['nav.operations/nav.studentProfiles'],
            $this->studentMenuEntries($this->admin())
        );

        $this->assertSame(
            ['nav.coach/nav.myStudents'],
            $this->studentMenuEntries($coach)
        );

        $this->assertSame([], $this->studentMenuEntries(User::factory()->create(['role' => 'member'])));
    }

    public function test_the_coach_my_students_mock_route_is_gone(): void
    {
        [$coach] = $this->coach();

        $this->actingAs($coach)->get('/cms/coach/my-students')->assertNotFound();
    }

    public function test_an_admin_still_sees_the_whole_student_directory(): void
    {
        StudentProfile::factory()->count(2)->create();

        $props = $this->actingAs($this->admin())->get('/cms/operations/students')->viewData('page')['props'];

        $this->assertCount(2, $props['profiles']['data']);
        $this->assertSame(2, $props['stats']['total']);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** @return string[] "group/label" for every menu item pointing at the student directory */
    private function studentMenuEntries(User $user): array
    {
        $entries = [];

        foreach (app(MenuRegistry::class)->forUser($user) as $group) {
            foreach ($group['items'] as $item) {
                if ($item['href'] === '/cms/operations/students') {
                    $entries[] = $group['labelKey'].'/'.$item['labelKey'];
                }
            }
        }

        return $entries;
    }

    private function coach(): array
    {
        $user = User::factory()->create(['role' => 'coach']);

        return [$user, CoachProfile::factory()->create(['user_id' => $user->id])];
    }

    private function managerWithoutMedicalAccess(): User
    {
        $user = User::factory()->create(['role' => 'coach']);
        $user->givePermissionTo(['operations.students.view', 'operations.students.manage']);

        return $user;
    }
}
