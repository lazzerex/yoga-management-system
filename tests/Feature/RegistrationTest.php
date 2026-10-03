<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_a_self_registered_account_holds_the_member_role_and_its_permissions(): void
    {
        $this->register();

        $user = User::where('username', 'newperson')->firstOrFail();

        $this->assertSame('member', $user->role);
        $this->assertTrue($user->hasRole('member'));
        $this->assertTrue($user->can('member.dashboard.view'));
        $this->assertTrue($user->can('member.enrollments.manage'));
    }

    public function test_a_self_registered_account_gets_a_student_profile_so_it_can_book(): void
    {
        $this->register();

        $user = User::where('username', 'newperson')->firstOrFail();

        $this->assertNotNull($user->studentProfile);
        $this->assertTrue($user->studentProfile->is_active);
    }

    public function test_a_self_registered_account_can_reach_the_member_pages(): void
    {
        $this->register();

        $user = User::where('username', 'newperson')->firstOrFail();

        $this->actingAs($user)->get('/cms/member/book')->assertOk();
        $this->actingAs($user)->get('/cms/member/my-classes')->assertOk();
        $this->actingAs($user)->get('/cms/member/my-membership')->assertOk();
    }

    public function test_a_self_registered_account_is_given_a_menu(): void
    {
        $this->register();

        $user = User::where('username', 'newperson')->firstOrFail();

        $menu = $this->actingAs($user)->get('/cms/dashboard')->inertiaProps('menu');

        $this->assertNotEmpty($menu, 'a signed-up member should not land on an empty shell');
    }

    private function register(): void
    {
        $this->post('/cms/register', [
            'name' => 'New Person',
            'username' => 'newperson',
            'email' => 'new@example.test',
            'password' => 'password-is-long',
            'password_confirmation' => 'password-is-long',
        ]);
    }
}
