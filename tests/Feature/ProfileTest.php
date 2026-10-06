<?php

namespace Tests\Feature;

use App\Models\ClassType;
use App\Models\CoachProfile;
use App\Models\LoginLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_any_user_can_set_and_clear_their_own_picture(): void
    {
        // An admin has neither a coach nor a student profile and still needs an avatar.
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post('/cms/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.png')])
            ->assertRedirect();

        $media = $admin->fresh()->getFirstMedia('avatar');
        $this->assertNotNull($media);
        $this->actingAs($admin)->get("/cms/operations/files/{$media->id}")->assertOk();

        $this->actingAs($admin)
            ->post('/cms/profile/avatar', ['remove_avatar' => true])
            ->assertRedirect();

        $this->assertNull($admin->fresh()->getFirstMedia('avatar'));
    }

    public function test_a_picture_uploaded_from_the_profile_page_belongs_to_the_user(): void
    {
        $user = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->post('/cms/profile/avatar', ['avatar' => UploadedFile::fake()->image('me.png')]);

        $media = $user->fresh()->getFirstMedia('avatar');
        $this->assertSame(User::class, $media->model_type);
        $this->assertSame($user->id, $media->model_id);

        // A member holds no directory permission, so someone else's picture stays closed.
        $stranger = User::factory()->create(['role' => 'member']);
        $this->actingAs($stranger)->get("/cms/operations/files/{$media->id}")->assertForbidden();
    }

    public function test_a_picture_that_is_not_an_image_is_rejected(): void
    {
        $user = User::factory()->create(['role' => 'member']);

        $this->actingAs($user)
            ->post('/cms/profile/avatar', ['avatar' => UploadedFile::fake()->create('payload.php', 8, 'text/x-php')])
            ->assertSessionHasErrors('avatar');

        $this->assertNull($user->fresh()->getFirstMedia('avatar'));
    }

    public function test_authenticated_user_can_view_profile_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Ari Morgan',
            'username' => 'arimorgan',
            'email' => 'ari@example.com',
            'role' => 'coach',
        ]);

        LoginLog::create([
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit Browser',
            'device_type' => 'desktop',
            'logged_in_at' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($user)->get('/cms/profile');

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Profile/Show')
            ->where('profile.name', 'Ari Morgan')
            ->where('profile.username', 'arimorgan')
            ->where('profile.email', 'ari@example.com')
            ->where('profile.role', 'coach')
            ->where('loginStats.total_sign_ins', 1)
            ->has('recentLogins', 1)
        );
    }

    public function test_a_coach_updates_their_own_coach_profile(): void
    {
        $user = User::factory()->create(['role' => 'coach']);
        $profile = CoachProfile::factory()->create(['user_id' => $user->id, 'bio' => 'Old bio']);
        $profile->classTypes()->sync([ClassType::factory()->create()->id]);

        $this->actingAs($user)
            ->patch('/cms/coach/profile', [
                'bio' => 'Hatha and Yin for beginners.',
                'years_experience' => 7,
                'certifications' => 'RYT-500',
                'class_type_ids' => [],
                'is_active' => false,
            ])
            ->assertSessionHasNoErrors();

        $profile->refresh();
        $this->assertSame('Hatha and Yin for beginners.', $profile->bio);
        $this->assertSame(7, $profile->years_experience);
        $this->assertSame('RYT-500', $profile->certifications);
        $this->assertTrue($profile->is_active);
        $this->assertCount(1, $profile->classTypes);
    }

    public function test_the_profile_page_offers_the_coach_form_only_to_a_coach(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);
        CoachProfile::factory()->create(['user_id' => $coach->id]);
        $member = User::factory()->create(['role' => 'member']);

        $this->actingAs($coach)->get('/cms/profile')
            ->assertInertia(fn (Assert $page) => $page->whereNot('endpoints.coachProfile', null));

        $this->actingAs($member)->get('/cms/profile')
            ->assertInertia(fn (Assert $page) => $page->where('endpoints.coachProfile', null));
    }

    public function test_a_member_cannot_reach_the_coach_profile_update(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'member']))
            ->patch('/cms/coach/profile', ['bio' => 'x'])
            ->assertForbidden();
    }

    public function test_guest_is_redirected_from_profile_page(): void
    {
        $response = $this->get('/cms/profile');

        $response->assertRedirect('/cms/login');
    }
}
