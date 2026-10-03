<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\LessonPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPlanTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_coach_can_create_a_draft_lesson_plan(): void
    {
        [$user, $coachProfile] = $this->coach();

        $this->actingAs($user)
            ->post('/cms/operations/lesson-planning', $this->planPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('lesson_plans', [
            'coach_profile_id' => $coachProfile->id,
            'title' => 'Hip Mobility Flow',
            'status' => 'draft',
            'submitted_at' => null,
        ]);
    }

    public function test_an_admin_cannot_author_a_lesson_plan(): void
    {
        $this->actingAs($this->admin())
            ->get('/cms/operations/lesson-planning/create')
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->post('/cms/operations/lesson-planning', $this->planPayload())
            ->assertForbidden();
    }

    public function test_the_full_cycle_runs_draft_to_pending_to_rejected_to_resubmit_to_approved(): void
    {
        [$user] = $this->coach();
        $admin = $this->admin();
        $plan = $this->planFor($user);

        $this->actingAs($user)->post("/cms/operations/lesson-planning/{$plan->id}/submit")->assertRedirect();
        $this->assertSame('pending', $plan->fresh()->status);
        $this->assertNotNull($plan->fresh()->submitted_at);

        $this->actingAs($admin)
            ->post("/cms/operations/lesson-planning/{$plan->id}/review", ['action' => 'rejected', 'comment' => 'Add a warm-up.'])
            ->assertRedirect();
        $this->assertSame('rejected', $plan->fresh()->status);

        $this->actingAs($user)
            ->patch("/cms/operations/lesson-planning/{$plan->id}", $this->planPayload(['title' => 'Reworked Flow']))
            ->assertRedirect();
        $plan->refresh();
        $this->assertSame('Reworked Flow', $plan->title);
        $this->assertSame('rejected', $plan->status, 'Saving an edit must not resubmit the plan.');

        $this->actingAs($user)->post("/cms/operations/lesson-planning/{$plan->id}/submit")->assertRedirect();
        $this->assertSame('pending', $plan->fresh()->status);

        $this->actingAs($admin)
            ->post("/cms/operations/lesson-planning/{$plan->id}/review", ['action' => 'approved'])
            ->assertRedirect();
        $this->assertSame('approved', $plan->fresh()->status);

        $this->assertSame(2, $plan->reviews()->count());
    }

    public function test_a_reviewer_cannot_approve_their_own_plan(): void
    {
        $admin = $this->admin();
        $coachProfile = CoachProfile::factory()->create(['user_id' => $admin->id]);
        $plan = LessonPlan::factory()->pending()->create(['coach_profile_id' => $coachProfile->id]);

        $this->actingAs($admin)
            ->from("/cms/operations/lesson-planning/{$plan->id}")
            ->post("/cms/operations/lesson-planning/{$plan->id}/review", ['action' => 'approved'])
            ->assertSessionHasErrors('action');

        $this->assertSame('pending', $plan->fresh()->status);
        $this->assertSame(0, $plan->reviews()->count());
    }

    public function test_rejecting_without_a_comment_is_refused(): void
    {
        [$user] = $this->coach();
        $plan = $this->planFor($user, 'pending');

        $this->actingAs($this->admin())
            ->from("/cms/operations/lesson-planning/{$plan->id}")
            ->post("/cms/operations/lesson-planning/{$plan->id}/review", ['action' => 'rejected'])
            ->assertSessionHasErrors('comment');

        $this->assertSame('pending', $plan->fresh()->status);
    }

    public function test_a_pending_plan_cannot_be_edited_or_deleted(): void
    {
        [$user] = $this->coach();
        $plan = $this->planFor($user, 'pending');

        $this->actingAs($user)->get("/cms/operations/lesson-planning/{$plan->id}/edit")->assertForbidden();

        $this->actingAs($user)
            ->from("/cms/operations/lesson-planning/{$plan->id}")
            ->patch("/cms/operations/lesson-planning/{$plan->id}", $this->planPayload(['title' => 'Sneaky edit']))
            ->assertSessionHasErrors('action');

        $this->actingAs($user)
            ->from("/cms/operations/lesson-planning/{$plan->id}")
            ->delete("/cms/operations/lesson-planning/{$plan->id}")
            ->assertSessionHasErrors('action');

        $this->assertDatabaseHas('lesson_plans', ['id' => $plan->id, 'title' => $plan->title]);
    }

    public function test_an_approved_plan_cannot_be_edited(): void
    {
        [$user] = $this->coach();
        $plan = $this->planFor($user, 'approved');

        $this->actingAs($user)
            ->from("/cms/operations/lesson-planning/{$plan->id}")
            ->patch("/cms/operations/lesson-planning/{$plan->id}", $this->planPayload(['title' => 'Sneaky edit']))
            ->assertSessionHasErrors('action');
    }

    public function test_only_a_draft_can_be_deleted(): void
    {
        [$user] = $this->coach();
        $draft = $this->planFor($user);
        $rejected = $this->planFor($user, 'rejected');

        $this->actingAs($user)
            ->from("/cms/operations/lesson-planning/{$rejected->id}")
            ->delete("/cms/operations/lesson-planning/{$rejected->id}")
            ->assertSessionHasErrors('action');

        $this->actingAs($user)
            ->delete("/cms/operations/lesson-planning/{$draft->id}")
            ->assertRedirect('/cms/operations/lesson-planning');

        $this->assertDatabaseMissing('lesson_plans', ['id' => $draft->id]);
        $this->assertDatabaseHas('lesson_plans', ['id' => $rejected->id]);
    }

    public function test_a_coach_cannot_see_or_touch_another_coaches_plan(): void
    {
        [$user] = $this->coach();
        [$otherUser] = $this->coach();
        $plan = $this->planFor($otherUser);

        $this->actingAs($user)->get("/cms/operations/lesson-planning/{$plan->id}")->assertForbidden();
        $this->actingAs($user)->get("/cms/operations/lesson-planning/{$plan->id}/edit")->assertForbidden();
        $this->actingAs($user)->post("/cms/operations/lesson-planning/{$plan->id}/submit")->assertForbidden();
    }

    public function test_the_index_shows_a_coach_only_their_own_plans_but_an_admin_every_plan(): void
    {
        $branch = Branch::factory()->create();
        [$user] = $this->coach();
        [$otherUser] = $this->coach();
        $mine = $this->planFor($user, 'draft', $branch);
        $theirs = $this->planFor($otherUser, 'draft', $branch);

        $props = $this->actingAs($user)->get('/cms/operations/lesson-planning')->viewData('page')['props'];
        $this->assertSame([$mine->id], array_column($props['plans']['data'], 'id'));

        $props = $this->actingAs($this->admin())->get('/cms/operations/lesson-planning')->viewData('page')['props'];
        $this->assertEqualsCanonicalizing([$mine->id, $theirs->id], array_column($props['plans']['data'], 'id'));
    }

    public function test_the_index_and_queue_only_show_plans_from_the_current_branch(): void
    {
        [$user] = $this->coach();
        $here = Branch::factory()->create(['name' => 'Alpha Studio']);
        $elsewhere = Branch::factory()->create(['name' => 'Zeta Studio']);
        $visible = $this->planFor($user, 'pending', $here);
        $this->planFor($user, 'pending', $elsewhere);

        $props = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $here->id)
            ->get('/cms/operations/lesson-planning')
            ->viewData('page')['props'];
        $this->assertSame([$visible->id], array_column($props['plans']['data'], 'id'));
        $this->assertSame(1, $props['stats']['pending']);

        $props = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $here->id)
            ->get('/cms/operations/lesson-planning/pending')
            ->viewData('page')['props'];
        $this->assertSame([$visible->id], array_column($props['plans']['data'], 'id'));
    }

    public function test_a_coach_cannot_open_the_approval_queue(): void
    {
        [$user] = $this->coach();

        $this->actingAs($user)->get('/cms/operations/lesson-planning/pending')->assertForbidden();
        $this->actingAs($this->admin())->get('/cms/operations/lesson-planning/pending')->assertOk();
    }

    public function test_a_member_cannot_reach_lesson_plans_at_all(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($member)->get('/cms/operations/lesson-planning')->assertForbidden();
        $this->actingAs($member)->get('/cms/operations/lesson-planning/create')->assertForbidden();
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

    private function planFor(User $user, string $status = 'draft', ?Branch $branch = null): LessonPlan
    {
        return LessonPlan::factory()->create([
            'coach_profile_id' => $user->coachProfile->id,
            'branch_id' => $branch?->id ?? Branch::factory(),
            'status' => $status,
            'submitted_at' => $status === 'draft' ? null : now()->subDay(),
        ]);
    }

    private function planPayload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => Branch::factory()->create()->id,
            'class_type_id' => ClassType::factory()->create()->id,
            'class_session_id' => null,
            'title' => 'Hip Mobility Flow',
            'objective' => 'Open the hips safely.',
            'asana_sequence' => "Tadasana\nBalasana",
            'duration_minutes' => 60,
            'level' => 'beginner',
        ], $overrides);
    }
}
