<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Menu\Facades\Menu;
use App\Support\Menu\MenuRegistry;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MenuHookTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_the_facade_and_a_type_hinted_registry_are_the_same_instance(): void
    {
        $this->assertSame(app('app-menu'), app(MenuRegistry::class));
    }

    public function test_a_registry_resolved_by_class_still_yields_every_menu_group(): void
    {
        // Core items used to be poked straight onto the facade instance, so resolving
        // MenuRegistry by class silently produced a menu with no nav.main group.
        $groups = array_column(app(MenuRegistry::class)->forUser($this->admin()), 'labelKey');

        $this->assertSame(['nav.main', 'nav.operations', 'nav.admin'], $groups);
    }

    public function test_the_core_items_come_from_the_register_hook(): void
    {
        $main = collect(Menu::forUser($this->admin()))->firstWhere('labelKey', 'nav.main');

        $this->assertSame(['nav.home', 'nav.myProfile'], array_column($main['items'], 'labelKey'));
    }

    public function test_tuition_owns_its_invoices_and_plans_children(): void
    {
        $tuition = $this->tuitionItem($this->admin());

        $this->assertNotNull($tuition, 'operations should carry a tuition item');
        $this->assertSame(
            ['nav.tuitionInvoices', 'nav.tuitionPlans'],
            array_column($tuition['children'], 'labelKey'),
        );
    }

    public function test_tuition_plans_no_longer_hangs_off_the_settings_menu(): void
    {
        $settings = $this->settingsItem($this->admin());

        $this->assertNotNull($settings, 'the admin group should carry a settings item');
        $this->assertNotContains('nav.tuitionPlans', array_column($settings['children'], 'labelKey'));
    }

    public function test_each_tuition_child_respects_its_own_permission(): void
    {
        $admin = $this->admin();

        // Revoked on the role, since that is where the permission is granted.
        Role::findByName('admin')->revokePermissionTo('operations.tuition.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $tuition = $this->tuitionItem($admin->fresh());

        $this->assertNotNull($tuition, 'tuition itself is gated on the view permission');
        $this->assertSame(['nav.tuitionInvoices'], array_column($tuition['children'], 'labelKey'));
    }

    public function test_no_two_menu_entries_share_one_href_for_a_single_viewer(): void
    {
        // Two leaf items with the same href both match the active-link check and the
        // sidebar highlights both, so a viewer must never see a duplicate.
        foreach (['admin', 'coach', 'member'] as $role) {
            $hrefs = $this->leafHrefs(Menu::forUser(User::factory()->create(['role' => $role])));
            $duplicates = array_keys(array_filter(array_count_values($hrefs), fn (int $n) => $n > 1));

            $this->assertSame([], $duplicates, "{$role} sees a duplicated menu href");
        }
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function tuitionItem(User $user): ?array
    {
        $operations = collect(Menu::forUser($user))->firstWhere('labelKey', 'nav.operations');

        return collect($operations['items'] ?? [])->firstWhere('labelKey', 'nav.tuition');
    }

    private function settingsItem(User $user): ?array
    {
        $admin = collect(Menu::forUser($user))->firstWhere('labelKey', 'nav.admin');

        return collect($admin['items'] ?? [])->firstWhere('labelKey', 'nav.settings');
    }

    /** @return string[] every clickable href in the menu, parents included when they link */
    private function leafHrefs(array $groups): array
    {
        $hrefs = [];

        $walk = function (array $items) use (&$walk, &$hrefs): void {
            foreach ($items as $item) {
                // A parent with children renders as a non-link label, so it cannot clash.
                if ($item['href'] !== null && $item['children'] === []) {
                    $hrefs[] = $item['href'];
                }

                $walk($item['children']);
            }
        };

        foreach ($groups as $group) {
            $walk($group['items']);
        }

        return $hrefs;
    }
}
