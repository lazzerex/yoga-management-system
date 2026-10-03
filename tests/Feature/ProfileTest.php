<?php

namespace Tests\Feature;

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
            'two_factor_confirmed_at' => now()->subDay(),
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
            ->where('security.two_factor_enabled', true)
            ->where('loginStats.total_sign_ins', 1)
            ->has('recentLogins', 1)
        );
    }

    public function test_guest_is_redirected_from_profile_page(): void
    {
        $response = $this->get('/cms/profile');

        $response->assertRedirect('/cms/login');
    }
}
