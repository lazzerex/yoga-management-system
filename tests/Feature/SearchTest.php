<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\Enrollment;
use App\Models\LessonPlan;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_coach_only_finds_students_booked_into_their_own_sessions(): void
    {
        [$mine, $mineProfile] = $this->coach();
        [, $theirsProfile] = $this->coach();

        $ours = $this->student('Ha Nguyen');
        $theirs = $this->student('Ha Tran');
        $this->book($ours, $mineProfile);
        $this->book($theirs, $theirsProfile);

        $rows = $this->rowsFor($mine, 'Ha', 'students');
        $this->assertSame(['Ha Nguyen'], array_column($rows, 'title'));

        $rows = $this->rowsFor($this->admin(), 'Ha', 'students');
        $this->assertEqualsCanonicalizing(['Ha Nguyen', 'Ha Tran'], array_column($rows, 'title'));
    }

    public function test_a_coach_only_finds_their_own_lesson_plans(): void
    {
        [$mine, $mineProfile] = $this->coach();
        [, $theirsProfile] = $this->coach();

        // Lesson plans are branch scoped on their index page, so the search follows the same branch.
        $branch = Branch::factory()->create();
        LessonPlan::factory()->create(['coach_profile_id' => $mineProfile->id, 'branch_id' => $branch->id, 'title' => 'Hip Mobility Flow']);
        LessonPlan::factory()->create(['coach_profile_id' => $theirsProfile->id, 'branch_id' => $branch->id, 'title' => 'Hip Opening Flow']);

        $rows = $this->rowsFor($mine, 'Hip', 'lessonPlans', $branch);
        $this->assertSame(['Hip Mobility Flow'], array_column($rows, 'title'));

        $rows = $this->rowsFor($this->admin(), 'Hip', 'lessonPlans', $branch);
        $this->assertEqualsCanonicalizing(
            ['Hip Mobility Flow', 'Hip Opening Flow'],
            array_column($rows, 'title')
        );
    }

    public function test_a_coach_never_receives_the_admin_only_groups(): void
    {
        [$coach] = $this->coach();
        $this->student('Ha Nguyen');
        CoachProfile::factory()->create(['user_id' => User::factory()->create(['role' => 'coach', 'name' => 'Ha Le'])->id]);

        $groups = array_column($this->groupsFor($coach, 'Ha'), 'key');

        $this->assertNotContains('coaches', $groups);
        $this->assertNotContains('users', $groups);
        $this->assertNotContains('invoices', $groups);
    }

    public function test_a_group_is_capped_at_five_rows(): void
    {
        $admin = $this->admin();

        foreach (range(1, 7) as $i) {
            $this->student("Ha Student {$i}");
        }

        $this->assertCount(5, $this->rowsFor($admin, 'Ha Student', 'students'));
    }

    public function test_a_query_shorter_than_two_characters_returns_nothing(): void
    {
        $admin = $this->admin();
        $this->student('Ha Nguyen');

        $this->assertSame([], $this->groupsFor($admin, 'H'));
        $this->assertSame([], $this->groupsFor($admin, ''));
    }

    public function test_a_member_cannot_search_and_the_box_is_hidden(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $props = $this->actingAs($member)->get('/cms/dashboard')->viewData('page')['props'];
        $this->assertFalse($props['auth']['user']['canSearch']);

        $this->assertSame([], $this->groupsFor($member, 'Ha'));
    }

    public function test_the_endpoint_is_throttled(): void
    {
        $admin = $this->admin();

        foreach (range(1, 30) as $i) {
            $this->actingAs($admin)->getJson('/cms/search?q=Ha')->assertOk();
        }

        $this->actingAs($admin)->getJson('/cms/search?q=Ha')->assertStatus(429);
    }

    private function groupsFor(User $user, string $term, ?Branch $branch = null): array
    {
        $request = $this->actingAs($user);

        if ($branch) {
            $request = $request->withUnencryptedCookie('branch_id', $branch->id);
        }

        return $request->getJson('/cms/search?q='.urlencode($term))
            ->assertOk()
            ->json('groups');
    }

    private function rowsFor(User $user, string $term, string $group, ?Branch $branch = null): array
    {
        foreach ($this->groupsFor($user, $term, $branch) as $candidate) {
            if ($candidate['key'] === $group) {
                return $candidate['rows'];
            }
        }

        return [];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function coach(): array
    {
        $user = User::factory()->create(['role' => 'coach']);

        return [$user, CoachProfile::factory()->create(['user_id' => $user->id])];
    }

    private function student(string $name): StudentProfile
    {
        return StudentProfile::factory()->create([
            'user_id' => User::factory()->create(['role' => 'member', 'name' => $name])->id,
        ]);
    }

    private function book(StudentProfile $student, CoachProfile $coach): void
    {
        $session = ClassSession::factory()->create(['coach_profile_id' => $coach->id]);

        Enrollment::factory()->create([
            'class_session_id' => $session->id,
            'student_profile_id' => $student->id,
            'status' => 'booked',
        ]);
    }
}
