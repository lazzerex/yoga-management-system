<?php

namespace Tests\Feature;

use App\Models\AiSuggestion;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\LessonPlan;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiLessonPlanTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    private const ENDPOINT = 'generativelanguage.googleapis.com/*';

    protected function setUp(): void
    {
        parent::setUp();

        // No test may open a socket, on either driver.
        Http::preventStrayRequests();
    }

    public function test_the_seeder_grants_the_suggest_permission_to_coach_only(): void
    {
        $this->assertTrue(User::factory()->create(['role' => 'coach'])->can('operations.plans.ai.suggest'));
        $this->assertFalse(User::factory()->create(['role' => 'admin'])->can('operations.plans.ai.suggest'));
    }

    public function test_a_coach_gets_a_sequence_and_the_row_records_it_as_a_sequence(): void
    {
        [$user] = $this->coach();

        $response = $this->actingAs($user)->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload());

        $response->assertOk();
        $this->assertNotEmpty($response->json('steps'));

        $row = AiSuggestion::sole();
        $this->assertSame(AiSuggestion::KIND_SEQUENCE, $row->kind);
        $this->assertSame($user->id, $row->user_id);
        $this->assertSame('fake', $row->model);
    }

    public function test_the_prompt_carries_only_the_four_whitelisted_inputs(): void
    {
        [$user] = $this->coach();
        $classType = ClassType::factory()->create(['name' => 'Vinyasa']);

        $this->actingAs($user)->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload([
            'class_type_id' => $classType->id,
            'objective' => 'Open the hips safely.',
        ]))->assertOk();

        $prompt = AiSuggestion::sole()->prompt;

        $this->assertSame([
            'Class type: Vinyasa',
            'Level: beginner',
            'Duration: 60 minutes',
            'Objective: Open the hips safely.',
        ], explode("\n", $prompt));
    }

    public function test_an_admin_cannot_reach_the_suggest_endpoint(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload())
            ->assertForbidden();
    }

    public function test_a_failed_call_returns_an_error_and_saves_nothing(): void
    {
        config(['services.gemini.driver' => 'gemini', 'services.gemini.key' => 'test-key']);
        Http::fake([self::ENDPOINT => Http::response('nope', 500)]);

        [$user] = $this->coach();

        $this->actingAs($user)
            ->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload())
            ->assertOk()
            ->assertJson(['error' => 'operations.aiUnavailable']);

        $this->assertSame(0, AiSuggestion::count());
    }

    public function test_a_timeout_returns_an_error_and_saves_nothing(): void
    {
        config(['services.gemini.driver' => 'gemini', 'services.gemini.key' => 'test-key']);
        Http::fake([self::ENDPOINT => fn () => throw new ConnectionException('timed out')]);

        [$user] = $this->coach();

        $this->actingAs($user)
            ->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload())
            ->assertOk()
            ->assertJson(['error' => 'operations.aiUnavailable']);

        $this->assertSame(0, AiSuggestion::count());
    }

    public function test_a_candidate_that_did_not_finish_is_treated_as_no_suggestion(): void
    {
        config(['services.gemini.driver' => 'gemini', 'services.gemini.key' => 'test-key']);
        Http::fake([self::ENDPOINT => Http::response([
            'candidates' => [['finishReason' => 'MAX_TOKENS', 'content' => ['parts' => [['text' => '{"steps":[']]]]],
        ])]);

        [$user] = $this->coach();

        $this->actingAs($user)
            ->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload())
            ->assertOk()
            ->assertJson(['error' => 'operations.aiUnavailable']);

        $this->assertSame(0, AiSuggestion::count());
    }

    public function test_a_live_call_returns_the_candidate_text(): void
    {
        config(['services.gemini.driver' => 'gemini', 'services.gemini.key' => 'test-key', 'services.gemini.model' => 'gemini-3.5-flash']);
        $this->fakeGemini(['steps' => [['name' => 'Mountain', 'sanskrit' => 'Tadasana', 'duration' => '5 breaths', 'cue' => 'Stack the joints.']]]);

        [$user] = $this->coach();

        $this->actingAs($user)
            ->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload())
            ->assertOk()
            ->assertJsonPath('steps.0.sanskrit', 'Tadasana');

        $this->assertSame('gemini-3.5-flash', AiSuggestion::sole()->model);

        // The reply must be asked for as JSON, or the model answers in Markdown.
        Http::assertSent(fn ($request) => $request->data()['generationConfig']['responseMimeType'] === 'application/json'
            && isset($request->data()['generationConfig']['responseSchema']));
    }

    public function test_a_reply_that_is_not_json_is_treated_as_no_suggestion(): void
    {
        config(['services.gemini.driver' => 'gemini', 'services.gemini.key' => 'test-key']);
        Http::fake([self::ENDPOINT => Http::response([
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => '**Warm-up** then Tadasana']]]]],
        ])]);

        [$user] = $this->coach();

        $this->actingAs($user)
            ->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload())
            ->assertOk()
            ->assertJson(['error' => 'operations.aiUnavailable']);

        $this->assertSame(0, AiSuggestion::count());
    }

    public function test_the_suggest_endpoint_is_throttled(): void
    {
        [$user] = $this->coach();
        $payload = $this->suggestPayload();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->postJson('/cms/operations/lesson-planning/suggest', $payload)->assertOk();
        }

        $this->actingAs($user)
            ->postJson('/cms/operations/lesson-planning/suggest', $payload)
            ->assertStatus(429);
    }

    public function test_an_admin_checks_a_pending_plan_and_the_plan_is_untouched(): void
    {
        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'pending');
        $before = $plan->only(['title', 'objective', 'asana_sequence', 'status', 'level', 'duration_minutes']);

        $response = $this->actingAs($this->admin())->postJson("/cms/operations/lesson-planning/{$plan->id}/check");

        $response->assertOk();
        $this->assertNotEmpty($response->json('sections'));
        $this->assertSame(AiSuggestion::KIND_REVIEW, AiSuggestion::sole()->kind);

        // "AI never auto-approves" is a requirement, so it is asserted and not merely intended.
        $this->assertSame($before, $plan->fresh()->only(array_keys($before)));
        $this->assertSame(0, $plan->reviews()->count());
    }

    public function test_a_check_carries_a_sequence_grade_clamped_to_the_scale(): void
    {
        config(['services.gemini.driver' => 'live', 'services.gemini.key' => 'test-key']);
        $this->fakeGemini([
            'grade' => ['score' => 140, 'verdict' => 'Strong opening, thin cool-down.'],
            'sections' => [['heading' => 'Safety', 'issue' => 'None.', 'fix' => 'None.', 'severity' => 'ok']],
        ]);

        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'pending');

        $response = $this->actingAs($this->admin())->postJson("/cms/operations/lesson-planning/{$plan->id}/check");

        $response->assertOk();
        $this->assertSame(100, $response->json('grade.score'));
        $this->assertSame('Strong opening, thin cool-down.', $response->json('grade.verdict'));
    }

    /** The findings are the review. A reply without a grade still has to produce one. */
    public function test_a_check_without_a_grade_still_returns_the_findings(): void
    {
        config(['services.gemini.driver' => 'live', 'services.gemini.key' => 'test-key']);
        $this->fakeGemini(['sections' => [['heading' => 'Safety', 'issue' => 'None.', 'fix' => 'None.', 'severity' => 'ok']]]);

        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'pending');

        $response = $this->actingAs($this->admin())->postJson("/cms/operations/lesson-planning/{$plan->id}/check");

        $response->assertOk();
        $this->assertCount(1, $response->json('sections'));
        $this->assertNull($response->json('grade'));
    }

    public function test_a_coach_cannot_reach_the_check_endpoint(): void
    {
        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'pending');

        [$otherCoach] = $this->coach();

        $this->actingAs($otherCoach)
            ->postJson("/cms/operations/lesson-planning/{$plan->id}/check")
            ->assertForbidden();
    }

    public function test_a_plan_that_is_not_pending_cannot_be_checked(): void
    {
        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'approved');

        $this->actingAs($this->admin())
            ->postJson("/cms/operations/lesson-planning/{$plan->id}/check")
            ->assertForbidden();
    }

    public function test_no_image_is_sent_when_the_box_is_left_unticked(): void
    {
        config(['services.gemini.driver' => 'gemini', 'services.gemini.key' => 'test-key']);
        $this->fakeGemini(['sections' => [['heading' => 'Safety', 'issue' => 'None.', 'fix' => 'None.', 'severity' => 'ok']]]);

        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'pending');
        $this->attachImage($plan);

        $this->actingAs($this->admin())
            ->postJson("/cms/operations/lesson-planning/{$plan->id}/check", ['media_id' => null])
            ->assertOk();

        Http::assertSent(function ($request) {
            $parts = $request->data()['contents'][0]['parts'];

            return count($parts) === 1 && ! isset($parts[0]['inline_data']);
        });

        $this->assertDatabaseMissing('audit_logs', ['action' => 'ai_attachment_sent']);
    }

    public function test_a_ticked_image_is_sent_once_and_audited_once(): void
    {
        config(['services.gemini.driver' => 'gemini', 'services.gemini.key' => 'test-key']);
        $this->fakeGemini(['sections' => [['heading' => 'Safety', 'issue' => 'None.', 'fix' => 'None.', 'severity' => 'ok']]]);

        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'pending');
        $media = $this->attachImage($plan);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson("/cms/operations/lesson-planning/{$plan->id}/check", ['media_id' => $media->id])
            ->assertOk();

        Http::assertSent(function ($request) {
            $parts = $request->data()['contents'][0]['parts'];

            return count($parts) === 2 && $parts[1]['inline_data']['mime_type'] === 'image/jpeg';
        });

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ai_attachment_sent',
            'causer_id' => $admin->id,
            'subject_id' => $coachUser->id,
        ]);
        $this->assertSame(1, AuditLog::where('action', 'ai_attachment_sent')->count());

        // The image never lands in the evidence table; the row records that one went.
        $this->assertStringContainsString("#{$media->id}", AiSuggestion::sole()->prompt);
    }

    public function test_an_image_belonging_to_another_plan_is_rejected(): void
    {
        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'pending');
        $other = $this->planFor($coachUser, 'pending');
        $media = $this->attachImage($other);

        $this->actingAs($this->admin())
            ->postJson("/cms/operations/lesson-planning/{$plan->id}/check", ['media_id' => $media->id])
            ->assertStatus(422)
            ->assertJson(['error' => 'operations.aiImageInvalid']);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'ai_attachment_sent']);
    }

    public function test_a_pdf_attachment_cannot_be_sent(): void
    {
        [$coachUser] = $this->coach();
        $plan = $this->planFor($coachUser, 'pending');
        $media = $plan->addMedia(UploadedFile::fake()->create('waiver.pdf', 20, 'application/pdf'))
            ->toMediaCollection('attachments');

        $this->actingAs($this->admin())
            ->postJson("/cms/operations/lesson-planning/{$plan->id}/check", ['media_id' => $media->id])
            ->assertStatus(422)
            ->assertJson(['error' => 'operations.aiImageInvalid']);

        $this->assertDatabaseMissing('audit_logs', ['action' => 'ai_attachment_sent']);
    }

    public function test_the_buttons_are_hidden_when_the_driver_is_live_without_a_key(): void
    {
        config(['services.gemini.driver' => 'gemini', 'services.gemini.key' => null]);

        [$coachUser] = $this->coach();

        $this->actingAs($coachUser)
            ->get('/cms/operations/lesson-planning/create')
            ->assertInertia(fn ($page) => $page->where('endpoints.suggest', null));

        $this->actingAs($coachUser)
            ->postJson('/cms/operations/lesson-planning/suggest', $this->suggestPayload())
            ->assertForbidden();
    }

    /**
     * A schema-constrained reply arrives as a JSON document inside the candidate text.
     */
    private function fakeGemini(array $payload): void
    {
        Http::fake([self::ENDPOINT => Http::response([
            'candidates' => [['finishReason' => 'STOP', 'content' => ['parts' => [['text' => json_encode($payload)]]]]],
        ])]);
    }

    private function suggestPayload(array $overrides = []): array
    {
        return array_merge([
            'class_type_id' => ClassType::factory()->create()->id,
            'level' => 'beginner',
            'duration_minutes' => 60,
            'objective' => 'Open the hips safely.',
        ], $overrides);
    }

    private function attachImage(LessonPlan $plan)
    {
        return $plan->addMedia(UploadedFile::fake()->image('board.jpg'))->toMediaCollection('attachments');
    }

    private function coach(): array
    {
        $user = User::factory()->create(['role' => 'coach']);

        return [$user, CoachProfile::factory()->create(['user_id' => $user->id])];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function planFor(User $user, string $status): LessonPlan
    {
        return LessonPlan::factory()->create([
            'coach_profile_id' => $user->coachProfile->id,
            'branch_id' => Branch::factory(),
            'status' => $status,
            'submitted_at' => now()->subDay(),
        ]);
    }
}
