<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserCreateTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_an_admin_created_member_gets_a_student_profile(): void
    {
        $user = $this->createAs('member');

        $this->assertNotNull($user->studentProfile);
        $this->assertNull($user->coachProfile);
    }

    public function test_an_admin_created_coach_gets_a_coach_profile(): void
    {
        $user = $this->createAs('coach');

        $this->assertNotNull($user->coachProfile);
        $this->assertTrue($user->coachProfile->is_active);
        $this->assertNull($user->studentProfile);
    }

    public function test_an_admin_created_admin_gets_no_profile(): void
    {
        $user = $this->createAs('admin');

        $this->assertNull($user->coachProfile);
        $this->assertNull($user->studentProfile);
    }

    private function createAs(string $role): User
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/cms/admin/users', [
                'name' => 'New Person',
                'username' => 'newperson',
                'email' => 'newperson@example.com',
                'role' => $role,
                'password' => 'NewPass@12345',
                'password_confirmation' => 'NewPass@12345',
            ])
            ->assertSessionHasNoErrors();

        return User::where('username', 'newperson')->firstOrFail();
    }
}
