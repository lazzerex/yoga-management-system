<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_the_forgot_password_page_renders(): void
    {
        $this->get('/cms/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_requesting_a_link_emails_the_account_owner(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/cms/forgot-password', ['email' => $user->email])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_the_reset_page_carries_the_token_and_email(): void
    {
        $this->get('/cms/reset-password/abc123?email=someone@example.com')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/ResetPassword')
                ->where('token', 'abc123')
                ->where('email', 'someone@example.com'));
    }

    public function test_a_valid_token_sets_a_new_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/cms/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPass@12345',
            'password_confirmation' => 'NewPass@12345',
        ])->assertRedirect('/cms/login');

        $this->assertTrue(Hash::check('NewPass@12345', $user->fresh()->password));
    }

    public function test_the_confirm_password_page_renders_for_a_signed_in_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/cms/user/confirm-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ConfirmPassword'));
    }

    public function test_confirming_with_the_right_password_is_accepted_and_a_wrong_one_is_not(): void
    {
        $user = User::factory()->create(['password' => 'Member@12345']);

        $this->actingAs($user)
            ->post('/cms/user/confirm-password', ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)
            ->post('/cms/user/confirm-password', ['password' => 'Member@12345'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_two_factor_routes_are_not_registered(): void
    {
        $this->get('/cms/two-factor-challenge')->assertNotFound();
    }
}
