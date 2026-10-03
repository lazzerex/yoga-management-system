<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchContextTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PermissionSeeder::class;

    public function test_current_branch_defaults_to_the_first_active_branch_by_name(): void
    {
        Branch::factory()->create(['name' => 'Zeta Studio']);
        $first = Branch::factory()->create(['name' => 'Alpha Studio']);

        $page = $this->actingAs($this->admin())
            ->get('/cms/dashboard')
            ->viewData('page');

        $this->assertSame($first->id, $page['props']['currentBranch']['id']);
    }

    public function test_current_branch_respects_a_valid_cookie(): void
    {
        Branch::factory()->create();
        $chosen = Branch::factory()->create();

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $chosen->id)
            ->get('/cms/dashboard')
            ->viewData('page');

        $this->assertSame($chosen->id, $page['props']['currentBranch']['id']);
    }

    public function test_current_branch_falls_back_when_the_cookie_points_at_an_inactive_branch(): void
    {
        $active = Branch::factory()->create(['name' => 'Alpha Studio']);
        $inactive = Branch::factory()->inactive()->create(['name' => 'Zeta Studio']);

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', $inactive->id)
            ->get('/cms/dashboard')
            ->viewData('page');

        $this->assertSame($active->id, $page['props']['currentBranch']['id']);
    }

    public function test_current_branch_falls_back_when_the_cookie_is_garbage(): void
    {
        $first = Branch::factory()->create(['name' => 'Alpha Studio']);

        $page = $this->actingAs($this->admin())
            ->withUnencryptedCookie('branch_id', 'not-a-number')
            ->get('/cms/dashboard')
            ->viewData('page');

        $this->assertSame($first->id, $page['props']['currentBranch']['id']);
    }

    public function test_all_branches_prop_only_includes_active_branches(): void
    {
        Branch::factory()->create(['name' => 'Active One']);
        Branch::factory()->inactive()->create(['name' => 'Inactive One']);

        $page = $this->actingAs($this->admin())
            ->get('/cms/dashboard')
            ->viewData('page');

        $names = collect($page['props']['allBranches'])->pluck('name');
        $this->assertTrue($names->contains('Active One'));
        $this->assertFalse($names->contains('Inactive One'));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }
}
