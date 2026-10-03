<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ClassSession;
use App\Models\CoachProfile;
use App\Models\Enrollment;
use App\Models\Invoice;
use App\Models\LessonPlan;
use App\Models\StudentProfile;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('media');
    }

    public function test_uploads_never_land_on_a_publicly_served_disk(): void
    {
        $profile = $this->coachProfile();

        $this->actingAs($this->admin())
            ->patch("/cms/operations/coaches/{$profile->id}", $this->coachPayload(['avatar' => UploadedFile::fake()->image('face.png')]))
            ->assertRedirect();

        $media = Media::firstOrFail();

        // config/filesystems.php marks "local" serve => true, which publishes GET /storage/{path}.
        $this->assertSame('media', $media->disk);
        $this->assertNotSame('local', $media->disk);
        $this->assertNotSame('public', $media->disk);
    }

    public function test_an_avatar_replaces_the_one_held_and_can_be_removed(): void
    {
        $admin = $this->admin();
        $profile = $this->coachProfile();

        $this->actingAs($admin)->patch("/cms/operations/coaches/{$profile->id}", $this->coachPayload([
            'avatar' => UploadedFile::fake()->image('first.png'),
        ]));
        $this->assertSame(1, Media::count());
        $this->assertSame('first', Media::firstOrFail()->name);

        $this->actingAs($admin)->patch("/cms/operations/coaches/{$profile->id}", $this->coachPayload([
            'avatar' => UploadedFile::fake()->image('second.png'),
        ]));
        $this->assertSame(1, Media::count());
        $this->assertSame('second', Media::firstOrFail()->name);

        $this->actingAs($admin)->patch("/cms/operations/coaches/{$profile->id}", $this->coachPayload(['remove_avatar' => true]));
        $this->assertSame(0, Media::count());
    }

    public function test_an_oversized_or_wrong_type_upload_is_refused(): void
    {
        $admin = $this->admin();
        $profile = $this->coachProfile();

        $this->actingAs($admin)
            ->patch("/cms/operations/coaches/{$profile->id}", $this->coachPayload([
                'avatar' => UploadedFile::fake()->create('notes.txt', 10, 'text/plain'),
            ]))
            ->assertSessionHasErrors('avatar');

        $this->actingAs($admin)
            ->patch("/cms/operations/coaches/{$profile->id}", $this->coachPayload([
                'avatar' => UploadedFile::fake()->image('huge.png')->size(3000),
            ]))
            ->assertSessionHasErrors('avatar');

        $this->assertSame(0, Media::count());
    }

    public function test_a_payment_proof_is_downloadable_only_by_someone_who_manages_tuition(): void
    {
        $media = $this->paymentProof();
        $url = "/cms/operations/files/{$media->id}";

        $this->actingAs($this->admin())->get($url)->assertOk();
        $this->actingAs($this->coach()->user)->get($url)->assertForbidden();
        $this->actingAs($this->member())->get($url)->assertForbidden();
    }

    public function test_a_payment_proof_cannot_be_deleted_by_anyone(): void
    {
        $media = $this->paymentProof();

        // Voiding replaces deleting; even the library-wide files.manage holder is refused.
        $this->actingAs($this->admin())
            ->delete("/cms/operations/files/{$media->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('media', ['id' => $media->id]);
    }

    public function test_a_lesson_plan_attachment_is_visible_to_its_author_and_to_a_reviewer_but_not_to_another_coach(): void
    {
        $author = $this->coach();
        $stranger = $this->coach();
        $plan = LessonPlan::factory()->create(['coach_profile_id' => $author->id, 'status' => 'draft']);
        $media = $plan->addMedia(UploadedFile::fake()->create('sequence.pdf', 20, 'application/pdf'))
            ->toMediaCollection('attachments');

        $url = "/cms/operations/files/{$media->id}";

        $this->actingAs($author->user)->get($url)->assertOk();
        $this->actingAs($this->admin())->get($url)->assertOk();
        $this->actingAs($stranger->user)->get($url)->assertForbidden();
    }

    public function test_an_author_may_delete_their_own_attachment_while_the_plan_is_editable(): void
    {
        $author = $this->coach();
        $plan = LessonPlan::factory()->create(['coach_profile_id' => $author->id, 'status' => 'draft']);
        $media = $plan->addMedia(UploadedFile::fake()->create('sequence.pdf', 20, 'application/pdf'))
            ->toMediaCollection('attachments');

        $this->actingAs($author->user)->delete("/cms/operations/files/{$media->id}")->assertRedirect();
        $this->assertDatabaseMissing('media', ['id' => $media->id]);

        $locked = LessonPlan::factory()->create(['coach_profile_id' => $author->id, 'status' => 'approved']);
        $lockedMedia = $locked->addMedia(UploadedFile::fake()->create('other.pdf', 20, 'application/pdf'))
            ->toMediaCollection('attachments');

        $this->actingAs($author->user)->delete("/cms/operations/files/{$lockedMedia->id}")->assertForbidden();
        $this->assertDatabaseHas('media', ['id' => $lockedMedia->id]);
    }

    public function test_a_plan_cannot_hold_more_attachments_than_the_cap(): void
    {
        $author = $this->coach();
        $plan = LessonPlan::factory()->create(['coach_profile_id' => $author->id, 'status' => 'draft']);

        foreach (range(1, LessonPlan::MAX_ATTACHMENTS) as $i) {
            $plan->addMedia(UploadedFile::fake()->create("held-{$i}.pdf", 10, 'application/pdf'))
                ->toMediaCollection('attachments');
        }

        $this->actingAs($author->user)
            ->patch("/cms/operations/lesson-planning/{$plan->id}", $this->planPayload($plan, [
                'attachments' => [UploadedFile::fake()->create('one-too-many.pdf', 10, 'application/pdf')],
            ]))
            ->assertSessionHasErrors('attachments');

        $this->assertSame(LessonPlan::MAX_ATTACHMENTS, $plan->fresh()->getMedia('attachments')->count());
    }

    public function test_a_member_downloads_their_own_photo_but_not_another_members(): void
    {
        $mine = $this->studentProfile();
        $theirs = $this->studentProfile();

        $myMedia = $mine->user->addMedia(UploadedFile::fake()->image('me.png'))->toMediaCollection('avatar');
        $theirMedia = $theirs->user->addMedia(UploadedFile::fake()->image('them.png'))->toMediaCollection('avatar');

        $this->actingAs($mine->user)->get("/cms/operations/files/{$myMedia->id}")->assertOk();
        $this->actingAs($mine->user)->get("/cms/operations/files/{$theirMedia->id}")->assertForbidden();
    }

    public function test_a_coach_reaches_a_student_photo_only_for_a_student_booked_into_their_own_session(): void
    {
        $coach = $this->coach();
        $mine = $this->studentProfile();
        $stranger = $this->studentProfile();

        $session = ClassSession::factory()->create(['coach_profile_id' => $coach->id]);
        Enrollment::factory()->create([
            'student_profile_id' => $mine->id,
            'class_session_id' => $session->id,
            'status' => 'booked',
        ]);

        $mineMedia = $mine->user->addMedia(UploadedFile::fake()->image('mine.png'))->toMediaCollection('avatar');
        $strangerMedia = $stranger->user->addMedia(UploadedFile::fake()->image('stranger.png'))->toMediaCollection('avatar');

        $this->actingAs($coach->user)->get("/cms/operations/files/{$mineMedia->id}")->assertOk();
        $this->actingAs($coach->user)->get("/cms/operations/files/{$strangerMedia->id}")->assertForbidden();
    }

    public function test_the_library_lists_only_what_the_viewer_may_download(): void
    {
        $proof = $this->paymentProof();
        $coach = $this->coach();
        $stranger = $this->coach();
        $ownMedia = $coach->user->addMedia(UploadedFile::fake()->image('own.png'))->toMediaCollection('avatar');
        $strangerMedia = $stranger->user->addMedia(UploadedFile::fake()->image('stranger.png'))->toMediaCollection('avatar');

        $adminIds = $this->libraryIds($this->admin());
        $this->assertContains($proof->id, $adminIds);
        $this->assertContains($ownMedia->id, $adminIds);
        $this->assertContains($strangerMedia->id, $adminIds);

        // A coach holds files.view but not coaches.view, so the directory narrows to their own record.
        $coachIds = $this->libraryIds($coach->user);
        $this->assertNotContains($proof->id, $coachIds);
        $this->assertNotContains($strangerMedia->id, $coachIds);
        $this->assertContains($ownMedia->id, $coachIds);
    }

    public function test_a_member_cannot_reach_the_library_at_all(): void
    {
        $this->actingAs($this->member())->get('/cms/operations/file-library')->assertForbidden();
    }

    private function libraryIds(User $user): array
    {
        return array_column(
            $this->actingAs($user)->get('/cms/operations/file-library')->viewData('page')['props']['files']['data'],
            'id'
        );
    }

    private function paymentProof(): Media
    {
        $invoice = Invoice::factory()->create(['total_amount' => 500000]);

        $this->actingAs($this->admin())->post("/cms/operations/tuition-fees/{$invoice->id}/payments", [
            'amount' => 500000,
            'method' => 'cash',
            'paid_at' => today()->toDateString(),
            'proof' => UploadedFile::fake()->create('receipt.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        return $invoice->payments()->firstOrFail()->getFirstMedia('proof');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function member(): User
    {
        return User::factory()->create(['role' => 'member']);
    }

    private function coach(): CoachProfile
    {
        return CoachProfile::factory()->create([
            'user_id' => User::factory()->create(['role' => 'coach'])->id,
        ]);
    }

    private function coachProfile(): CoachProfile
    {
        return $this->coach();
    }

    private function studentProfile(): StudentProfile
    {
        return StudentProfile::factory()->create([
            'user_id' => User::factory()->create(['role' => 'member'])->id,
        ]);
    }

    private function coachPayload(array $overrides = []): array
    {
        return array_merge([
            'bio' => 'Teaches Hatha.',
            'years_experience' => 5,
            'certifications' => 'RYT-200',
            'class_type_ids' => [],
            'is_active' => true,
        ], $overrides);
    }

    private function planPayload(LessonPlan $plan, array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $plan->branch_id ?: Branch::factory()->create()->id,
            'class_type_id' => $plan->class_type_id,
            'class_session_id' => null,
            'title' => $plan->title,
            'objective' => $plan->objective,
            'asana_sequence' => $plan->asana_sequence,
            'duration_minutes' => $plan->duration_minutes,
            'level' => $plan->level,
        ], $overrides);
    }
}
