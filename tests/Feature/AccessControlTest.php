<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_member_granted_the_permission_directly_can_reach_the_users_page(): void
    {
        $user = User::factory()->create(['role' => 'member']);
        $user->givePermissionTo('admin.users.view');
        $this->forgetPermissionCache();

        $this->actingAs($user)->get('/cms/admin/users')->assertOk();
    }

    public function test_signing_in_skips_an_intended_page_the_new_user_cannot_open(): void
    {
        $member = User::factory()->create(['role' => 'member']);

        // A guest request for an admin page records it as the intended URL.
        $this->get('/cms/admin/users')->assertRedirect('/cms/login');

        $this->post('/cms/login', ['username' => $member->username, 'password' => 'password'])
            ->assertRedirect('/cms/dashboard');
    }

    public function test_signing_in_still_follows_an_intended_page_the_user_can_open(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get('/cms/admin/users')->assertRedirect('/cms/login');

        $this->post('/cms/login', ['username' => $admin->username, 'password' => 'password'])
            ->assertRedirect(url('/cms/admin/users'));
    }

    public function test_admin_cannot_reach_the_users_page_once_the_permission_is_revoked(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Role::findByName('admin')->revokePermissionTo('admin.users.view');
        $this->forgetPermissionCache();

        $this->actingAs($user)->get('/cms/admin/users')->assertForbidden();
    }

    public function test_sidebar_menu_follows_permissions_not_role(): void
    {
        $member = User::factory()->create(['role' => 'member']);
        $this->assertNotContains('nav.users', $this->menuLabelKeys($member));

        $member->givePermissionTo('admin.users.view');
        $this->forgetPermissionCache();

        $this->assertContains('nav.users', $this->menuLabelKeys($member));
    }

    public function test_ability_flags_are_shared_for_each_role(): void
    {
        $expected = [
            'admin' => ['canAccessAdmin' => true, 'canViewCoachDashboard' => false, 'canViewSettings' => true, 'canViewMembership' => false],
            'coach' => ['canAccessAdmin' => false, 'canViewCoachDashboard' => true, 'canViewSettings' => false, 'canViewMembership' => false],
            'member' => ['canAccessAdmin' => false, 'canViewCoachDashboard' => false, 'canViewSettings' => false, 'canViewMembership' => true],
        ];

        foreach ($expected as $role => $flags) {
            $user = User::factory()->create(['role' => $role]);
            $page = $this->actingAs($user)->get('/cms/dashboard')->viewData('page');

            $this->assertSame($flags, [
                'canAccessAdmin' => $page['props']['auth']['user']['canAccessAdmin'],
                'canViewCoachDashboard' => $page['props']['auth']['user']['canViewCoachDashboard'],
                'canViewSettings' => $page['props']['auth']['user']['canViewSettings'],
                'canViewMembership' => $page['props']['auth']['user']['canViewMembership'],
            ], "flags wrong for {$role}");
        }
    }

    /** The quick menu reads these flags, so a changed permission has to move them. */
    public function test_the_settings_flag_follows_the_permission_not_the_role(): void
    {
        $coach = User::factory()->create(['role' => 'coach']);

        $page = $this->actingAs($coach)->get('/cms/dashboard')->viewData('page');
        $this->assertFalse($page['props']['auth']['user']['canViewSettings']);

        $coach->givePermissionTo('admin.settings.view');
        $this->forgetPermissionCache();

        $page = $this->actingAs($coach)->get('/cms/dashboard')->viewData('page');
        $this->assertTrue($page['props']['auth']['user']['canViewSettings']);
    }

    private function forgetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return string[] */
    private function menuLabelKeys(User $user): array
    {
        $page = $this->actingAs($user)->get('/cms/dashboard')->viewData('page');

        return collect($page['props']['menu'])
            ->flatMap(fn (array $group) => $group['items'])
            ->pluck('labelKey')
            ->all();
    }
}
