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

    public function test_dashboard_flags_are_shared_for_each_role(): void
    {
        $expected = [
            'admin' => ['canAccessAdmin' => true, 'canViewCoachDashboard' => false],
            'coach' => ['canAccessAdmin' => false, 'canViewCoachDashboard' => true],
            'member' => ['canAccessAdmin' => false, 'canViewCoachDashboard' => false],
        ];

        foreach ($expected as $role => $flags) {
            $user = User::factory()->create(['role' => $role]);
            $page = $this->actingAs($user)->get('/cms/dashboard')->viewData('page');

            $this->assertSame($flags, [
                'canAccessAdmin' => $page['props']['auth']['user']['canAccessAdmin'],
                'canViewCoachDashboard' => $page['props']['auth']['user']['canViewCoachDashboard'],
            ], "flags wrong for {$role}");
        }
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
